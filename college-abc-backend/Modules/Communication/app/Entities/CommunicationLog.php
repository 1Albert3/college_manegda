<?php

namespace Modules\Communication\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Traits\Searchable;

class CommunicationLog extends Model
{
    use HasFactory, Searchable;

    protected $fillable = [
        'channel',
        'provider',
        'recipient_type',
        'recipient_id',
        'recipient_address',
        'template_name',
        'subject',
        'content',
        'variables',
        'status',
        'error_message',
        'sent_at',
        'delivered_at',
        'opened_at',
        'clicked_at',
        'metadata',
        'priority',
        'attempts',
        'max_attempts',
        'next_retry_at',
        'batch_id',
        'user_id',
    ];

    protected $casts = [
        'variables' => 'array',
        'metadata' => 'array',
        'sent_at' => 'datetime',
        'delivered_at' => 'datetime',
        'opened_at' => 'datetime',
        'clicked_at' => 'datetime',
        'next_retry_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    protected $searchable = ['recipient_address', 'subject', 'template_name'];

    // Status constants
    const STATUS_PENDING = 'pending';
    const STATUS_PROCESSING = 'processing';
    const STATUS_SENT = 'sent';
    const STATUS_DELIVERED = 'delivered';
    const STATUS_FAILED = 'failed';
    const STATUS_CANCELLED = 'cancelled';

    // Channel constants
    const CHANNEL_EMAIL = 'email';
    const CHANNEL_SMS = 'sms';
    const CHANNEL_PUSH = 'push';
    const CHANNEL_IN_APP = 'in_app';

    // Priority constants
    const PRIORITY_LOW = 'low';
    const PRIORITY_NORMAL = 'normal';
    const PRIORITY_HIGH = 'high';
    const PRIORITY_URGENT = 'urgent';

    /**
     * Get the user that initiated this communication
     */
    public function user()
    {
        return $this->belongsTo(\Modules\Core\Entities\User::class);
    }

    /**
     * Get the recipient (polymorphic relationship)
     */
    public function recipient()
    {
        return $this->morphTo();
    }

    /**
     * Scope for filtering by status
     */
    public function scopeByStatus($query, $status)
    {
        return $query->where('status', $status);
    }

    /**
     * Scope for filtering by channel
     */
    public function scopeByChannel($query, $channel)
    {
        return $query->where('channel', $channel);
    }

    /**
     * Scope for filtering by template
     */
    public function scopeByTemplate($query, $template)
    {
        return $query->where('template_name', $template);
    }

    /**
     * Scope for pending communications
     */
    public function scopePending($query)
    {
        return $query->where('status', self::STATUS_PENDING);
    }

    /**
     * Scope for failed communications
     */
    public function scopeFailed($query)
    {
        return $query->where('status', self::STATUS_FAILED);
    }

    /**
     * Scope for communications ready for retry
     */
    public function scopeReadyForRetry($query)
    {
        return $query->where('status', self::STATUS_FAILED)
                    ->whereColumn('attempts', '<', 'max_attempts')
                    ->where(function ($q) {
                        $q->whereNull('next_retry_at')
                          ->orWhere('next_retry_at', '<=', now());
                    });
    }

    /**
     * Scope for filtering by date range
     */
    public function scopeDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('created_at', [$startDate, $endDate]);
    }

    /**
     * Check if communication can be retried
     */
    public function canRetry()
    {
        return $this->status === self::STATUS_FAILED
            && $this->attempts < $this->max_attempts;
    }

    /**
     * Mark as sent
     */
    public function markAsSent()
    {
        $this->update([
            'status' => self::STATUS_SENT,
            'sent_at' => now(),
        ]);
    }

    /**
     * Mark as delivered
     */
    public function markAsDelivered()
    {
        $this->update([
            'status' => self::STATUS_DELIVERED,
            'delivered_at' => now(),
        ]);
    }

    /**
     * Mark as failed
     */
    public function markAsFailed($errorMessage = null)
    {
        $this->update([
            'status' => self::STATUS_FAILED,
            'error_message' => $errorMessage,
            'attempts' => $this->attempts + 1,
            'next_retry_at' => $this->canRetry() ? now()->addMinutes(5 * $this->attempts) : null,
        ]);
    }

    /**
     * Mark as opened (for email tracking)
     */
    public function markAsOpened()
    {
        if (!$this->opened_at) {
            $this->update(['opened_at' => now()]);
        }
    }

    /**
     * Mark as clicked (for email tracking)
     */
    public function markAsClicked()
    {
        if (!$this->clicked_at) {
            $this->update(['clicked_at' => now()]);
        }
    }

    /**
     * Get formatted content (mask sensitive data)
     */
    public function getFormattedContentAttribute()
    {
        $content = $this->content;
        $sensitiveFields = config('communication.logging.sensitive_data', []);

        foreach ($sensitiveFields as $field) {
            $content = preg_replace('/' . $field . '\s*[:=]\s*([^\s,]+)/i', $field . ': ***', $content);
        }

        return $content;
    }

    /**
     * Get delivery rate for this communication
     */
    public function getDeliveryRateAttribute()
    {
        $total = self::where('batch_id', $this->batch_id)->count();
        $delivered = self::where('batch_id', $this->batch_id)
                        ->whereIn('status', [self::STATUS_SENT, self::STATUS_DELIVERED])
                        ->count();

        return $total > 0 ? round(($delivered / $total) * 100, 2) : 0;
    }
}
