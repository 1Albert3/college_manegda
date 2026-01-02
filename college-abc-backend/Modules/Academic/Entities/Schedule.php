<?php

namespace Modules\Academic\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Schedule extends Model
{
    use HasFactory;

    protected $fillable = [
        'class_room_id',
        'subject_id',
        'teacher_id',
        'academic_year_id',
        'day_of_week',
        'start_time',
        'end_time',
        'room',
        'notes',
    ];

    protected $casts = [
        'class_room_id' => 'integer',
        'subject_id' => 'integer',
        'teacher_id' => 'integer',
        'academic_year_id' => 'integer',
        'start_time' => 'datetime:H:i',
        'end_time' => 'datetime:H:i',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    // Day of week mapping
    const DAYS = [
        'monday' => 'Lundi',
        'tuesday' => 'Mardi',
        'wednesday' => 'Mercredi',
        'thursday' => 'Jeudi',
        'friday' => 'Vendredi',
        'saturday' => 'Samedi',
    ];

    // Time slots (common periods)
    const TIME_SLOTS = [
        ['start' => '08:00', 'end' => '09:00'],
        ['start' => '09:00', 'end' => '10:00'],
        ['start' => '10:15', 'end' => '11:15'],
        ['start' => '11:15', 'end' => '12:15'],
        ['start' => '14:00', 'end' => '15:00'],
        ['start' => '15:00', 'end' => '16:00'],
        ['start' => '16:15', 'end' => '17:15'],
    ];

    // Relations
    public function classRoom()
    {
        return $this->belongsTo(ClassRoom::class);
    }

    public function subject()
    {
        return $this->belongsTo(Subject::class);
    }

    public function teacher()
    {
        return $this->belongsTo(\Modules\Core\Entities\User::class, 'teacher_id');
    }

    public function academicYear()
    {
        return $this->belongsTo(AcademicYear::class);
    }

    // Scopes
    public function scopeByClass($query, int $classRoomId)
    {
        return $query->where('class_room_id', $classRoomId);
    }

    public function scopeByTeacher($query, int $teacherId)
    {
        return $query->where('teacher_id', $teacherId);
    }

    public function scopeBySubject($query, int $subjectId)
    {
        return $query->where('subject_id', $subjectId);
    }

    public function scopeByDay($query, string $day)
    {
        return $query->where('day_of_week', $day);
    }

    public function scopeByAcademicYear($query, int $academicYearId)
    {
        return $query->where('academic_year_id', $academicYearId);
    }

    public function scopeToday($query)
    {
        $today = strtolower(now()->format('l')); // monday, tuesday, etc.
        return $query->where('day_of_week', $today);
    }

    public function scopeOrdered($query)
    {
        return $query->orderByRaw("
            CASE day_of_week
                WHEN 'monday' THEN 1
                WHEN 'tuesday' THEN 2
                WHEN 'wednesday' THEN 3
                WHEN 'thursday' THEN 4
                WHEN 'friday' THEN 5
                WHEN 'saturday' THEN 6
            END
        ")->orderBy('start_time');
    }

    // Accessors
    public function getDayNameAttribute()
    {
        return self::DAYS[$this->day_of_week] ?? ucfirst($this->day_of_week);
    }

    public function getFormattedStartTimeAttribute()
    {
        return \Carbon\Carbon::parse($this->start_time)->format('H:i');
    }

    public function getFormattedEndTimeAttribute()
    {
        return \Carbon\Carbon::parse($this->end_time)->format('H:i');
    }

    public function getTimeSlotAttribute()
    {
        return $this->formatted_start_time . ' - ' . $this->formatted_end_time;
    }

    public function getDurationAttribute()
    {
        $start = \Carbon\Carbon::parse($this->start_time);
        $end = \Carbon\Carbon::parse($this->end_time);
        
        return $end->diffInMinutes($start);
    }

    public function getIsNowAttribute()
    {
        if ($this->day_of_week !== strtolower(now()->format('l'))) {
            return false;
        }

        $now = now()->format('H:i');
        $start = $this->formatted_start_time;
        $end = $this->formatted_end_time;

        return $now >= $start && $now < $end;
    }

    // Methods
    public function hasConflict()
    {
        // Check if teacher has another class at the same time
        $teacherConflict = static::where('teacher_id', $this->teacher_id)
            ->where('day_of_week', $this->day_of_week)
            ->where('academic_year_id', $this->academic_year_id)
            ->where('id', '!=', $this->id ?? 0)
            ->where(function($query) {
                $query->whereBetween('start_time', [$this->start_time, $this->end_time])
                      ->orWhereBetween('end_time', [$this->start_time, $this->end_time])
                      ->orWhere(function($q) {
                          $q->where('start_time', '<=', $this->start_time)
                            ->where('end_time', '>=', $this->end_time);
                      });
            })
            ->exists();

        // Check if class has another subject at the same time
        $classConflict = static::where('class_room_id', $this->class_room_id)
            ->where('day_of_week', $this->day_of_week)
            ->where('academic_year_id', $this->academic_year_id)
            ->where('id', '!=', $this->id ?? 0)
            ->where(function($query) {
                $query->whereBetween('start_time', [$this->start_time, $this->end_time])
                      ->orWhereBetween('end_time', [$this->start_time, $this->end_time])
                      ->orWhere(function($q) {
                          $q->where('start_time', '<=', $this->start_time)
                            ->where('end_time', '>=', $this->end_time);
                      });
            })
            ->exists();

        return $teacherConflict || $classConflict;
    }

    // Static methods
    public static function getClassSchedule(int $classRoomId, int $academicYearId = null)
    {
        $academicYearId = $academicYearId ?? AcademicYear::getCurrentYear()?->id;

        return static::byClass($classRoomId)
                    ->byAcademicYear($academicYearId)
                    ->with(['subject', 'teacher'])
                    ->ordered()
                    ->get()
                    ->groupBy('day_of_week');
    }

    public static function getTeacherSchedule(int $teacherId, int $academicYearId = null)
    {
        $academicYearId = $academicYearId ?? AcademicYear::getCurrentYear()?->id;

        return static::byTeacher($teacherId)
                    ->byAcademicYear($academicYearId)
                    ->with(['subject', 'classRoom'])
                    ->ordered()
                    ->get()
                    ->groupBy('day_of_week');
    }

    // Boot
    protected static function boot()
    {
        parent::boot();

        static::saving(function ($schedule) {
            // Validate no conflict before saving
            if ($schedule->hasConflict()) {
                throw new \Exception('Conflit d\'emploi du temps détecté : le professeur ou la classe a déjà un cours à cette heure.');
            }
        });
    }
}
