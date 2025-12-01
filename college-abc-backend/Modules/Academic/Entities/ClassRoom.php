<?php

namespace Modules\Academic\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Traits\HasUuid;
use App\Traits\Searchable;

class ClassRoom extends Model
{
    use HasFactory, HasUuid, Searchable;

    protected $table = 'class_rooms';

    protected $fillable = [
        'name', 'level', 'stream', 'capacity',
        'current_students_count', 'status',
        'description', 'timetable'
    ];

    protected $casts = [
        'capacity' => 'integer',
        'current_students_count' => 'integer',
        'timetable' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    protected $searchable = ['name', 'level', 'stream'];

    // Relations
    public function enrollments()
    {
        return $this->hasMany(\Modules\Student\Entities\Enrollment::class, 'class_id');
    }

    public function students()
    {
        return $this->hasManyThrough(
            \Modules\Student\Entities\Student::class,
            \Modules\Student\Entities\Enrollment::class,
            'class_id',
            'id',
            'id',
            'student_id'
        )->whereHas('enrollments', function ($query) {
            $query->where('status', 'active')
                  ->where('academic_year_id', AcademicYear::getCurrentYear()?->id);
        });
    }

    public function subjects()
    {
        return $this->belongsToMany(
            Subject::class,
            'class_subject',
            'class_id',
            'subject_id'
        )->withPivot(['academic_year_id', 'weekly_hours', 'coefficient'])
         ->withTimestamps();
    }

    public function currentSubjects()
    {
        $currentYearId = AcademicYear::getCurrentYear()?->id;

        return $this->belongsToMany(
            Subject::class,
            'class_subject',
            'class_id',
            'subject_id'
        )->wherePivot('academic_year_id', $currentYearId)
         ->withPivot(['academic_year_id', 'weekly_hours', 'coefficient'])
         ->withTimestamps();
    }

    public function classSubjects()
    {
        return $this->hasMany(ClassSubject::class, 'class_id');
    }

    public function attendances()
    {
        return $this->hasManyThrough(
            \Modules\Attendance\Entities\Attendance::class,
            \Modules\Student\Entities\Enrollment::class,
            'class_id',
            'student_id',
            'id',
            'student_id'
        );
    }

    public function grades()
    {
        return $this->hasManyThrough(
            \Modules\Grade\Entities\Grade::class,
            \Modules\Student\Entities\Enrollment::class,
            'class_id',
            'student_id',
            'id',
            'student_id'
        );
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function scopeByLevel($query, string $level)
    {
        return $query->where('level', $level);
    }

    public function scopeByStream($query, string $stream)
    {
        return $query->where('stream', $stream);
    }

    public function scopeOrderByLevel($query)
    {
        return $query->orderBy('level')->orderBy('stream')->orderBy('name');
    }

    // Accessors & Methods
    public function getDisplayNameAttribute()
    {
        $display = $this->name;

        if ($this->level) {
            $display .= ' (' . $this->level;
            if ($this->stream) {
                $display .= ' - ' . $this->stream;
            }
            $display .= ')';
        }

        return $display;
    }

    public function getAvailableSpotsAttribute()
    {
        if ($this->capacity === null) {
            return null; // Capacité illimitée
        }

        return max(0, $this->capacity - $this->current_students_count);
    }

    public function getOccupancyRateAttribute()
    {
        if ($this->capacity === null || $this->capacity === 0) {
            return 0;
        }

        return round(($this->current_students_count / $this->capacity) * 100, 1);
    }

    public function getFullNameAttribute()
    {
        return $this->name . ' - ' . $this->level . ($this->stream ? ' ' . $this->stream : '');
    }

    public function enrollStudent(\Modules\Student\Entities\Student $student, array $enrollmentData = [])
    {
        $enrollmentData = array_merge([
            'academic_year_id' => AcademicYear::getCurrentYear()?->id,
            'class_id' => $this->id,
            'enrollment_date' => now()->toDateString(),
            'status' => 'active',
            'discount_percentage' => 0,
        ], $enrollmentData);

        return $student->enrollments()->create($enrollmentData);
    }

    public function updateStudentsCount()
    {
        $count = $this->enrollments()
                     ->where('status', 'active')
                     ->where('academic_year_id', AcademicYear::getCurrentYear()?->id)
                     ->count();

        $this->update(['current_students_count' => $count]);

        return $this;
    }

    public function assignSubject(int $subjectId, array $attributes = [])
    {
        $attributes = array_merge([
            'weekly_hours' => 1,
            'coefficient' => 1,
            'academic_year_id' => AcademicYear::getCurrentYear()?->id,
        ], $attributes);

        $this->subjects()->attach($subjectId, $attributes);

        return $this;
    }

    public function removeSubject(int $subjectId)
    {
        $this->subjects()->detach($subjectId);

        return $this;
    }

    public function getAttendanceStats()
    {
        $attendances = $this->attendances()
                           ->whereDate('date', today())
                           ->get();

        $total = $this->current_students_count;
        $present = $attendances->where('status', 'present')->count();
        $absent = $attendances->where('status', 'absent')->count();
        $late = $attendances->where('status', 'late')->count();

        return [
            'total_students' => $total,
            'present' => $present,
            'absent' => $absent,
            'late' => $late,
            'attendance_rate' => $total > 0 ? round(($present / $total) * 100, 1) : 0,
        ];
    }

    // Static methods
    public static function getByLevelGrouped()
    {
        return static::active()
                    ->orderBy('level')
                    ->orderBy('name')
                    ->get()
                    ->groupBy(['level', 'stream'])
                    ->toArray();
    }

    public static function findByName(string $name)
    {
        return static::where('name', $name)->first();
    }
}
