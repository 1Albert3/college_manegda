<?php

namespace Modules\Attendance\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Traits\Searchable;
use Modules\Student\Entities\Student;
use Modules\Core\Entities\User;

class Attendance extends Model
{
    use HasFactory, Searchable;

    protected $fillable = [
        'student_id',
        'session_id',
        'status',
        'check_in_time',
        'check_out_time',
        'minutes_late',
        'justified',
        'justification_id',
        'absence_reason',
        'absence_notes',
        'admin_approved',
        'approved_by',
        'approved_at',
        'recorded_at',
        'recorded_by',
        'metadata',
        'teacher_notes',
        'admin_notes',
    ];

    protected $casts = [
        'check_in_time' => 'datetime',
        'check_out_time' => 'datetime',
        'approved_at' => 'datetime',
        'recorded_at' => 'datetime',
        'justified' => 'boolean',
        'admin_approved' => 'boolean',
        'metadata' => 'array',
        'minutes_late' => 'integer',
    ];

    protected $searchable = ['status', 'absence_reason'];

    // Relations
    public function student()
    {
        return $this->belongsTo(Student::class);
    }

    public function session()
    {
        return $this->belongsTo(Session::class);
    }

    public function recordedBy()
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }

    public function approvedBy()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function justification()
    {
        return $this->hasOne(Justification::class);
    }

    // Scopes
    public function scopePresent($query)
    {
        return $query->where('status', 'present');
    }

    public function scopeAbsent($query)
    {
        return $query->where('status', 'absent');
    }

    public function scopeLate($query)
    {
        return $query->where('status', 'late');
    }

    public function scopeExcused($query)
    {
        return $query->where('status', 'excused');
    }

    public function scopeJustified($query)
    {
        return $query->where('justified', true);
    }

    public function scopeByStudent($query, $studentId)
    {
        return $query->where('student_id', $studentId);
    }

    public function scopeBySession($query, $sessionId)
    {
        return $query->where('session_id', $sessionId);
    }

    public function scopeByDateRange($query, $startDate, $endDate)
    {
        return $query->whereHas('session', function ($q) use ($startDate, $endDate) {
            $q->whereBetween('date', [$startDate, $endDate]);
        });
    }

    public function scopeByClass($query, $classId)
    {
        return $query->whereHas('student.currentEnrollment', function ($q) use ($classId) {
            $q->where('class_id', $classId);
        });
    }

    // Accessors
    public function getIsLateAttribute()
    {
        return $this->status === 'late' || $this->minutes_late > 0;
    }

    public function getDurationAttribute()
    {
        if ($this->check_in_time && $this->check_out_time) {
            return $this->check_in_time->diffInMinutes($this->check_out_time);
        }
        return null;
    }

    public function getFormattedCheckInTimeAttribute()
    {
        return $this->check_in_time?->format('H:i');
    }

    public function getFormattedCheckOutTimeAttribute()
    {
        return $this->check_out_time?->format('H:i');
    }

    // Methods
    public function markAsPresent($checkInTime = null, $notes = null)
    {
        $this->update([
            'status' => 'present',
            'check_in_time' => $checkInTime ?? now(),
            'teacher_notes' => $notes,
        ]);
    }

    public function markAsAbsent($reason = null, $notes = null)
    {
        $this->update([
            'status' => 'absent',
            'absence_reason' => $reason,
            'absence_notes' => $notes,
        ]);
    }

    public function markAsLate($minutesLate, $checkInTime = null, $notes = null)
    {
        $this->update([
            'status' => 'late',
            'minutes_late' => $minutesLate,
            'check_in_time' => $checkInTime ?? now(),
            'teacher_notes' => $notes,
        ]);
    }

    public function markAsExcused($notes = null)
    {
        $this->update([
            'status' => 'excused',
            'teacher_notes' => $notes,
        ]);
    }

    public function checkOut($checkOutTime = null)
    {
        $this->update([
            'check_out_time' => $checkOutTime ?? now(),
        ]);
    }

    public function approve($approvedBy, $notes = null)
    {
        $this->update([
            'admin_approved' => true,
            'approved_by' => $approvedBy,
            'approved_at' => now(),
            'admin_notes' => $notes,
        ]);
    }

    public function rejectApproval($notes = null)
    {
        $this->update([
            'admin_approved' => false,
            'approved_by' => null,
            'approved_at' => null,
            'admin_notes' => $notes,
        ]);
    }

    public function addJustification($justificationData)
    {
        return $this->justification()->create($justificationData);
    }

    public function isJustified()
    {
        return $this->justified && $this->justification;
    }

    public function canBeJustified()
    {
        return $this->status === 'absent' && !$this->isJustified();
    }

    public function getStatusBadgeAttribute()
    {
        return match($this->status) {
            'present' => 'success',
            'absent' => 'danger',
            'late' => 'warning',
            'excused' => 'info',
            'partially_present' => 'secondary',
            default => 'light',
        };
    }

    public function getStatusLabelAttribute()
    {
        return match($this->status) {
            'present' => 'Présent',
            'absent' => 'Absent',
            'late' => 'En retard',
            'excused' => 'Excusé',
            'partially_present' => 'Présence partielle',
            default => 'Inconnu',
        };
    }
}
