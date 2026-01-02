<?php

namespace Modules\Academic\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Traits\Searchable;

class ClassRoom extends Model
{
    use HasFactory, Searchable;

    protected $table = 'class_rooms';

    protected $fillable = [
        'level_id',
        'academic_year_id',
        'main_teacher_id',
        'name',
        'room_number',
        'capacity',
        'description',
        'is_active'
    ];

    protected $casts = [
        'capacity' => 'integer',
        'is_active' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    protected $searchable = ['name', 'room_number'];

    // Relations
    public function level()
    {
        return $this->belongsTo(Level::class);
    }

    public function academicYear()
    {
        return $this->belongsTo(AcademicYear::class);
    }

    public function mainTeacher()
    {
        return $this->belongsTo(\Modules\Core\Entities\User::class, 'main_teacher_id');
    }

    public function enrollments()
    {
        return $this->hasMany(\Modules\Student\Entities\Enrollment::class, 'class_room_id');
    }

    public function students()
    {
        return $this->belongsToMany(
            \Modules\Student\Entities\Student::class,
            'enrollments',
            'class_room_id',
            'student_id'
        )->wherePivot('status', 'active')
            ->withPivot(['status', 'enrollment_date'])
            ->withTimestamps();
    }

    public function subjects()
    {
        return $this->belongsToMany(
            Subject::class,
            'class_subject',
            'class_room_id',
            'subject_id'
        )->withPivot(['hours_per_week', 'coefficient'])
            ->withTimestamps();
    }

    public function currentSubjects()
    {
        $currentYearId = AcademicYear::getCurrentYear()?->id;

        return $this->subjects()
            ->wherePivot('academic_year_id', $currentYearId);
    }

    public function classSubjects()
    {
        return $this->hasMany(ClassSubject::class, 'class_room_id');
    }

    public function attendances()
    {
        // Assuming Attendance has class_room_id directly
        return $this->hasMany(\Modules\Attendance\Entities\Attendance::class, 'class_room_id');
    }

    public function grades()
    {
        // Grades via Evaluation
        return $this->hasManyThrough(
            \Modules\Grade\Entities\Grade::class,
            \Modules\Grade\Entities\Evaluation::class,
            'class_room_id',
            'evaluation_id',
            'id',
            'id'
        );
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeByLevel($query, string $level)
    {
        return $query->where('level_id', $level);
    }

    public function scopeByStream($query, string $stream)
    {
        return $query;
    }

    public function scopeOrderByLevel($query)
    {
        return $query->orderBy('level_id')->orderBy('name');
    }

    // Accessors & Methods
    public function getDisplayNameAttribute()
    {
        return $this->name;
    }

    public function getAvailableSpotsAttribute()
    {
        if ($this->capacity === null) {
            return null;
        }
        // Assuming enrollments relation count or explicit column
        return max(0, $this->capacity - $this->enrollments()->active()->count());
    }

    public function getOccupancyRateAttribute()
    {
        if ($this->capacity === null || $this->capacity === 0) {
            return 0;
        }
        return round(($this->enrollments()->active()->count() / $this->capacity) * 100, 1);
    }

    public function getFullNameAttribute()
    {
        return $this->name;
    }

    public function enrollStudent(\Modules\Student\Entities\Student $student, array $enrollmentData = [])
    {
        $enrollmentData = array_merge([
            'academic_year_id' => AcademicYear::getCurrentYear()?->id,
            'class_room_id' => $this->id,
            'enrollment_date' => now()->toDateString(),
            'status' => 'active',
            'discount_percentage' => 0,
        ], $enrollmentData);

        return $student->enrollments()->create($enrollmentData);
    }

    public function updateStudentsCount()
    {
        // Optional: Update a cached count column if it exists
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

        $total = $this->enrollments()->active()->count();
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
            ->orderBy('level_id')
            ->orderBy('name')
            ->get()
            ->groupBy('level_id')
            ->toArray();
    }

    public static function findByName(string $name)
    {
        return static::where('name', $name)->first();
    }
}
