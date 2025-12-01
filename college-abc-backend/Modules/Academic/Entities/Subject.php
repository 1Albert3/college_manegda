<?php

namespace Modules\Academic\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Traits\HasUuid;
use App\Traits\Searchable;

class Subject extends Model
{
    use HasFactory, HasUuid, Searchable;

    protected $fillable = [
        'name', 'code', 'category', 'description',
        'coefficients', 'weekly_hours', 'level_type',
        'is_active', 'program'
    ];

    protected $casts = [
        'coefficients' => 'integer',
        'weekly_hours' => 'integer',
        'is_active' => 'boolean',
        'program' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    protected $searchable = ['name', 'code', 'description'];

    // Relations
    public function teachers()
    {
        return $this->belongsToMany(
            \Modules\Core\Entities\User::class,
            'teacher_subject',
            'subject_id',
            'teacher_id'
        )->withPivot('academic_year_id')
         ->withTimestamps();
    }

    public function classes()
    {
        return $this->belongsToMany(
            ClassRoom::class,
            'class_subject',
            'subject_id',
            'class_id'
        )->withPivot(['academic_year_id', 'weekly_hours', 'coefficient'])
         ->withTimestamps();
    }

    public function teacherSubjects()
    {
        return $this->hasMany(TeacherSubject::class);
    }

    public function classSubjects()
    {
        return $this->hasMany(ClassSubject::class);
    }

    public function grades()
    {
        return $this->hasMany(\Modules\Grade\Entities\Grade::class);
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeByCategory($query, string $category)
    {
        return $query->where('category', $category);
    }

    public function scopeByLevelType($query, string $levelType)
    {
        return $query->where(function ($q) use ($levelType) {
            $q->where('level_type', $levelType)
              ->orWhere('level_type', 'both');
        });
    }

    // Accessors & Methods
    public function getDisplayNameAttribute()
    {
        return $this->name . ' (' . $this->code . ')';
    }

    public function getCurrentTeachers()
    {
        return $this->teachers()
                   ->wherePivot('academic_year_id', AcademicYear::getCurrentYear()?->id)
                   ->get();
    }

    public function assignToClass(int $classId, array $attributes = [])
    {
        $attributes = array_merge([
            'weekly_hours' => $this->weekly_hours ?? 1,
            'coefficient' => $this->coefficients ?? 1,
            'academic_year_id' => AcademicYear::getCurrentYear()?->id,
        ], $attributes);

        return $this->classes()->attach($classId, $attributes);
    }

    public function assignTeacher(int $teacherId, int $academicYearId = null)
    {
        $academicYearId = $academicYearId ?? AcademicYear::getCurrentYear()?->id;

        $this->teachers()->attach($teacherId, ['academic_year_id' => $academicYearId]);

        return $this;
    }

    public function getStudentsCount()
    {
        // Count unique students enrolled in classes that have this subject
        return \Modules\Student\Entities\Student::whereHas('currentEnrollment.class.subjects', function ($query) {
            $query->where('subjects.id', $this->id);
        })->count();
    }

    public function getAverageGradeForClass(int $classId, int $academicYearId = null)
    {
        $academicYearId = $academicYearId ?? AcademicYear::getCurrentYear()?->id;

        return $this->grades()
                   ->whereHas('student.currentEnrollment', function ($query) use ($classId, $academicYearId) {
                       $query->where('class_id', $classId)
                             ->where('academic_year_id', $academicYearId);
                   })
                   ->avg('score');
    }

    // Static methods
    public static function findByCode(string $code)
    {
        return static::where('code', $code)->first();
    }

    public static function getByCategoryGrouped()
    {
        return static::active()
                    ->orderBy('category')
                    ->orderBy('name')
                    ->get()
                    ->groupBy('category');
    }
}
