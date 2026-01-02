<?php

namespace Modules\Attendance\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Modules\Core\Entities\User;

class AbsenceJustification extends Model
{
    use HasFactory;

    protected $fillable = [
        'attendance_id',
        'type',
        'reason',
        'document_path',
        'submitted_date',
        'status',
        'reviewed_by',
        'reviewed_at',
        'review_notes',
    ];

    protected $casts = [
        'submitted_date' => 'date',
        'reviewed_at' => 'datetime',
    ];

    // Relations
    public function attendance()
    {
        return $this->belongsTo(Attendance::class);
    }

    public function reviewer()
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    // Scopes
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeApproved($query)
    {
        return $query->where('status', 'approved');
    }

    public function scopeRejected($query)
    {
        return $query->where('status', 'rejected');
    }

    // Accessors
    public function getTypeLabelAttribute()
    {
        $labels = [
            'medical' => 'Médical',
            'family' => 'Familial',
            'official' => 'Officiel',
            'other' => 'Autre',
        ];

        return $labels[$this->type] ?? $this->type;
    }

    // Methods
    public function approve(int $userId, ?string $notes = null)
    {
        $this->update([
            'status' => 'approved',
            'reviewed_by' => $userId,
            'reviewed_at' => now(),
            'review_notes' => $notes,
        ]);

        // Update attendance status to excused
        $this->attendance->markAsExcused();
    }

    public function reject(int $userId, ?string $notes = null)
    {
        $this->update([
            'status' => 'rejected',
            'reviewed_by' => $userId,
            'reviewed_at' => now(),
            'review_notes' => $notes,
        ]);
    }
}
