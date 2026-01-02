<?php

namespace Modules\Communication\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Traits\Searchable;

class CommunicationTemplate extends Model
{
    use HasFactory, Searchable;

    protected $fillable = [
        'name',
        'slug',
        'description',
        'channel',
        'subject',
        'content',
        'html_content',
        'variables',
        'is_active',
        'category',
        'priority',
        'metadata',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'variables' => 'array',
        'metadata' => 'array',
        'is_active' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    protected $searchable = ['name', 'slug', 'description', 'subject'];

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
     * Get the user who created this template
     */
    public function creator()
    {
        return $this->belongsTo(\Modules\Core\Entities\User::class, 'created_by');
    }

    /**
     * Get the user who last updated this template
     */
    public function updater()
    {
        return $this->belongsTo(\Modules\Core\Entities\User::class, 'updated_by');
    }

    /**
     * Get communication logs using this template
     */
    public function logs()
    {
        return $this->hasMany(CommunicationLog::class, 'template_name', 'slug');
    }

    /**
     * Scope for active templates
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope for filtering by channel
     */
    public function scopeByChannel($query, $channel)
    {
        return $query->where('channel', $channel);
    }

    /**
     * Scope for filtering by category
     */
    public function scopeByCategory($query, $category)
    {
        return $query->where('category', $category);
    }

    /**
     * Scope for filtering by priority
     */
    public function scopeByPriority($query, $priority)
    {
        return $query->where('priority', $priority);
    }

    /**
     * Render the template with variables
     */
    public function render(array $variables = []): string
    {
        $content = $this->channel === self::CHANNEL_EMAIL ? $this->html_content ?? $this->content : $this->content;

        // Merge with default variables
        $defaultVars = config('communication.templates.variables', []);
        $variables = array_merge($defaultVars, $variables);

        // Replace variables in content
        foreach ($variables as $key => $value) {
            $content = str_replace("{{{$key}}}", $value, $content);
            $content = str_replace("{{$key}}", $value, $content);
        }

        return $content;
    }

    /**
     * Render the subject (for email templates)
     */
    public function renderSubject(array $variables = []): string
    {
        if (!$this->subject) {
            return '';
        }

        // Merge with default variables
        $defaultVars = config('communication.templates.variables', []);
        $variables = array_merge($defaultVars, $variables);

        // Replace variables in subject
        $subject = $this->subject;
        foreach ($variables as $key => $value) {
            $subject = str_replace("{{{$key}}}", $value, $subject);
            $subject = str_replace("{{$key}}", $value, $subject);
        }

        return $subject;
    }

    /**
     * Get available variables for this template
     */
    public function getAvailableVariables(): array
    {
        return $this->variables ?? [];
    }

    /**
     * Validate that all required variables are provided
     */
    public function validateVariables(array $variables): array
    {
        $availableVars = $this->getAvailableVariables();
        $requiredVars = array_filter($availableVars, fn($var) => isset($var['required']) && $var['required']);
        $requiredKeys = array_keys($requiredVars);

        $missing = array_diff($requiredKeys, array_keys($variables));

        return $missing;
    }

    /**
     * Get template preview with sample data
     */
    public function getPreview(array $sampleData = []): array
    {
        $defaultSample = [
            'user_name' => 'Jean Dupont',
            'app_name' => config('app.name', 'College ABC'),
            'app_url' => config('app.url', 'https://example.com'),
        ];

        $sampleData = array_merge($defaultSample, $sampleData);

        return [
            'subject' => $this->renderSubject($sampleData),
            'content' => $this->render($sampleData),
            'channel' => $this->channel,
            'variables' => $this->getAvailableVariables(),
        ];
    }

    /**
     * Duplicate this template
     */
    public function duplicate(string $newName = null, string $newSlug = null): self
    {
        $data = $this->toArray();
        unset($data['id'], $data['created_at'], $data['updated_at']);

        $data['name'] = $newName ?? $this->name . ' (Copy)';
        $data['slug'] = $newSlug ?? $this->slug . '-copy';
        $data['is_active'] = false; // New templates start inactive

        return self::create($data);
    }

    /**
     * Get usage statistics
     */
    public function getUsageStats(): array
    {
        $logs = $this->logs();

        return [
            'total_sent' => $logs->count(),
            'sent_today' => $logs->whereDate('created_at', today())->count(),
            'sent_this_week' => $logs->whereBetween('created_at', [now()->startOfWeek(), now()->endOfWeek()])->count(),
            'sent_this_month' => $logs->whereMonth('created_at', now()->month)->count(),
            'delivery_rate' => $logs->whereIn('status', ['sent', 'delivered'])->count() / max($logs->count(), 1) * 100,
            'failure_rate' => $logs->where('status', 'failed')->count() / max($logs->count(), 1) * 100,
        ];
    }

    /**
     * Boot method to generate slug
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($template) {
            if (!$template->slug) {
                $template->slug = str($template->name)->slug();
            }
        });

        static::updating(function ($template) {
            if ($template->isDirty('name') && !$template->isDirty('slug')) {
                $template->slug = str($template->name)->slug();
            }
        });
    }
}
