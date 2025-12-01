<?php

namespace Modules\Attendance\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Support\Facades\Auth;
use App\Traits\HasUuid;
use App\Traits\Searchable;
use Modules\Academic\Entities\AcademicYear;
use Modules\Academic\Entities\Subject;
use Modules\Academic\Entities\ClassRoom;
use Modules\Core\Entities\User;

class Session extends Model
{
    use HasFactory, HasUuid, Searchable;

    protected $table = 'class_sessions';

    protected $fillable = [
        'name',
        'description',
        'academic_year_id',
        'subject_id',
        'class_id',
        'teacher_id',
        'session_date',
        'start_time',
        'end_time',
        'duration_minutes',
        'type',
        'status',
        'actual_start_time',
        'actual_end_time',
        'room',
        'location_details',
        'topic',
        'objectives',
        'materials',
        'homework',
        'total_students',
        'present_count',
        'absent_count',
        'late_count',
        'excused_count',
        'attendance_validated',
        'teacher_notes',
        'admin_notes',
    ];

    protected $casts = [
        'session_date' => 'date',
        'start_time' => 'datetime:H:i',
        'end_time' => 'datetime:H:i',
        'actual_start_time' => 'datetime',
        'actual_end_time' => 'datetime',
        'attendance_validated' => 'boolean',
        'total_students' => 'integer',
        'present_count' => 'integer',
        'absent_count' => 'integer',
        'late_count' => 'integer',
        'excused_count' => 'integer',
        'duration_minutes' => 'integer',
    ];

    protected $searchable = ['name', 'topic', 'type'];

    // Relations
    public function academicYear()
    {
        return $this->belongsTo(AcademicYear::class);
    }

    public function subject()
    {
        return $this->belongsTo(Subject::class);
    }

    public function class()
    {
        return $this->belongsTo(ClassRoom::class, 'class_id');
    }

    public function teacher()
    {
        return $this->belongsTo(User::class, 'teacher_id');
    }

    public function attendances()
    {
        return $this->hasMany(Attendance::class);
    }

    // Scopes
    public function scopeScheduled($query)
    {
        return $query->where('status', 'scheduled');
    }

    public function scopeInProgress($query)
    {
        return $query->where('status', 'in_progress');
    }

    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    public function scopeCancelled($query)
    {
        return $query->where('status', 'cancelled');
    }

    public function scopeByDate($query, $date)
    {
        return $query->where('session_date', $date);
    }

    public function scopeByDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('session_date', [$startDate, $endDate]);
    }

    public function scopeByTeacher($query, $teacherId)
    {
        return $query->where('teacher_id', $teacherId);
    }

    public function scopeByClass($query, $classId)
    {
        return $query->where('class_id', $classId);
    }

    public function scopeBySubject($query, $subjectId)
    {
        return $query->where('subject_id', $subjectId);
    }

    public function scopeByType($query, $type)
    {
        return $query->where('type', $type);
    }

    public function scopeToday($query)
    {
        return $query->where('session_date', today());
    }

    public function scopeThisWeek($query)
    {
        return $query->whereBetween('session_date', [
            now()->startOfWeek(),
            now()->endOfWeek()
        ]);
    }

    // Accessors
    public function getFullNameAttribute()
    {
        return "{$this->subject->name} - {$this->class->name} - {$this->session_date->format('d/m/Y')} {$this->start_time->format('H:i')}-{$this->end_time->format('H:i')}";
    }

    public function getDurationAttribute()
    {
        return $this->start_time->diffInMinutes($this->end_time);
    }

    public function getAttendanceRateAttribute()
    {
        if ($this->total_students === 0) {
            return 0;
        }

        return round(($this->present_count / $this->total_students) * 100, 2);
    }

    public function getIsActiveAttribute()
    {
        return $this->status === 'in_progress';
    }

    public function getIsCompletedAttribute()
    {
        return $this->status === 'completed';
    }

    public function getIsCancelledAttribute()
    {
        return $this->status === 'cancelled';
    }

    public function getStatusLabelAttribute()
    {
        return match($this->status) {
            'scheduled' => 'Programmée',
            'in_progress' => 'En cours',
            'completed' => 'Terminée',
            'cancelled' => 'Annulée',
            default => 'Inconnu',
        };
    }

    public function getTypeLabelAttribute()
    {
        return match($this->type) {
            'regular' => 'Cours régulier',
            'exam' => 'Examen',
            'practical' => 'TP/Pratique',
            'special' => 'Cours spécial',
            default => 'Inconnu',
        };
    }

    // Methods
    public function start()
    {
        $this->update([
            'status' => 'in_progress',
            'actual_start_time' => now(),
        ]);
    }

    public function complete()
    {
        $this->update([
            'status' => 'completed',
            'actual_end_time' => now(),
        ]);

        $this->updateAttendanceStats();
    }

    public function cancel()
    {
        $this->update(['status' => 'cancelled']);
    }

    public function updateAttendanceStats()
    {
        $stats = $this->attendances()
            ->selectRaw('
                COUNT(*) as total,
                SUM(CASE WHEN status = "present" THEN 1 ELSE 0 END) as present,
                SUM(CASE WHEN status = "absent" THEN 1 ELSE 0 END) as absent,
                SUM(CASE WHEN status = "late" THEN 1 ELSE 0 END) as late,
                SUM(CASE WHEN status = "excused" THEN 1 ELSE 0 END) as excused
            ')
            ->first();

        $this->update([
            'total_students' => $stats->total,
            'present_count' => $stats->present ?? 0,
            'absent_count' => $stats->absent ?? 0,
            'late_count' => $stats->late ?? 0,
            'excused_count' => $stats->excused ?? 0,
        ]);
    }

    public function validateAttendance()
    {
        $this->update(['attendance_validated' => true]);
    }

    public function canTakeAttendance()
    {
        return in_array($this->status, ['scheduled', 'in_progress']);
    }

    public function isInProgress()
    {
        if (!$this->actual_start_time) {
            return false;
        }

        $now = now();
        return $now->between($this->actual_start_time, $this->actual_end_time ?? $this->end_time);
    }

    public function getStudentsForAttendance()
    {
        return $this->class->students()
            ->whereHas('enrollments', function ($q) {
                $q->where('academic_year_id', $this->academic_year_id)
                  ->where('status', 'active');
            })
            ->with('user')
            ->get();
    }

    public function bulkMarkAttendance(array $attendanceData)
    {
        $attendances = [];

        foreach ($attendanceData as $data) {
            $attendance = $this->attendances()->updateOrCreate(
                [
                    'student_id' => $data['student_id'],
                    'session_id' => $this->id,
                ],
                [
                    'status' => $data['status'],
                    'recorded_by' => Auth::id(),
                    'recorded_at' => now(),
                    'minutes_late' => $data['minutes_late'] ?? 0,
                    'absence_reason' => $data['absence_reason'] ?? null,
                    'absence_notes' => $data['absence_notes'] ?? null,
                    'teacher_notes' => $data['teacher_notes'] ?? null,
                    'metadata' => $data['metadata'] ?? null,
                ]
            );
            $attendances[] = $attendance;
        }

        // Mettre à jour les statistiques
        $this->updateAttendanceStats();

        return $attendances;
    }
}
