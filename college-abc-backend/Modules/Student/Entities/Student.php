<?php

namespace Modules\Student\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Traits\HasUuid;
use App\Traits\Searchable;
use Modules\Core\Entities\User;

class Student extends Model
{
    use HasFactory, SoftDeletes, HasUuid, Searchable;

    protected $fillable = [
        'user_id', 'matricule', 'first_name', 'last_name',
        'date_of_birth', 'gender', 'place_of_birth',
        'address', 'photo', 'status', 'medical_info'
    ];

    protected $casts = [
        'date_of_birth' => 'date',
        'medical_info' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    protected $searchable = ['matricule', 'first_name', 'last_name'];

    // Relations
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function parents()
    {
        return $this->belongsToMany(User::class, 'parent_student', 'student_id', 'parent_id')
                    ->withPivot('relationship', 'is_primary')
                    ->withTimestamps();
    }

    public function primaryParents()
    {
        return $this->parents()->wherePivot('is_primary', true);
    }

    public function enrollments()
    {
        return $this->hasMany(Enrollment::class);
    }

    public function currentEnrollment()
    {
        return $this->hasOne(Enrollment::class)
                    ->whereHas('academicYear', fn($q) => $q->where('is_current', true));
    }

    // public function attendances()
    // {
    //     return $this->hasMany(\Modules\Attendance\Entities\Attendance::class);
    // }

    public function grades()
    {
        return $this->hasMany(\Modules\Grade\Entities\Grade::class);
    }

    public function evaluations()
    {
        return $this->hasManyThrough(\Modules\Grade\Entities\Evaluation::class, \Modules\Grade\Entities\Grade::class, 'student_id', 'id', 'id', 'evaluation_id');
    }

    // public function currentClass()
    // {
    //     return $this->hasOneThrough(
    //         \Modules\Academic\Entities\ClassRoom::class,
    //         Enrollment::class,
    //         'student_id',
    //         'id',
    //         'id',
    //         'class_id'
    //     )->whereHas('academicYear', fn($q) => $q->where('is_current', true));
    // }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function scopeByClass($query, $classId)
    {
        return $query->whereHas('currentEnrollment', fn($q) => $q->where('class_id', $classId));
    }

    public function scopeByGender($query, $gender)
    {
        return $query->where('gender', $gender);
    }

    public function scopeOrderByName($query)
    {
        return $query->orderBy('last_name')->orderBy('first_name');
    }

    // Accessors
    public function getFullNameAttribute()
    {
        return "{$this->first_name} {$this->last_name}";
    }

    public function getAgeAttribute()
    {
        return $this->date_of_birth->age;
    }

    public function getCurrentClassNameAttribute()
    {
        return $this->currentClass?->name ?? 'Non inscrit';
    }

    public function getPrimaryParentAttribute()
    {
        return $this->primaryParents()->first();
    }

    // Methods
    public function isEnrolled()
    {
        return $this->currentEnrollment()->exists();
    }

    public function getAttendanceRate($startDate = null, $endDate = null)
    {
        $attendances = $this->attendances();

        if ($startDate) {
            $attendances->where('date', '>=', $startDate);
        }

        if ($endDate) {
            $attendances->where('date', '<=', $endDate);
        }

        $totalDays = $attendances->count();

        if ($totalDays === 0) {
            return 0;
        }

        $presentDays = $attendances->where('status', 'present')->count();

        return round(($presentDays / $totalDays) * 100, 2);
    }

    public function attachParent($parentId, $relationship, $isPrimary = false)
    {
        $this->parents()->syncWithoutDetaching([
            $parentId => [
                'relationship' => $relationship,
                'is_primary' => $isPrimary
            ]
        ]);
    }

    public function detachParent($parentId)
    {
        $this->parents()->detach($parentId);
    }

    public function generateMatricule()
    {
        $year = date('Y');
        $count = self::whereYear('created_at', $year)->count() + 1;

        return sprintf('STU%s%04d', $year, $count);
    }

    // Grade-related methods
    public function getAverageGrade($period = null, $academicYearId = null)
    {
        $grades = $this->grades()->present();

        if ($academicYearId) {
            $grades->byAcademicYear($academicYearId);
        }

        if ($period) {
            $grades->whereHas('evaluation', function ($q) use ($period) {
                $q->where('period', $period);
            });
        }

        return $grades->avg('weighted_score') ?? 0;
    }

    public function getGradesBySubject($subjectId, $academicYearId = null)
    {
        $grades = $this->grades()->bySubject($subjectId);

        if ($academicYearId) {
            $grades->byAcademicYear($academicYearId);
        }

        return $grades->present()->avg('weighted_score') ?? 0;
    }

    public function getSubjectAverage($subjectId, $period = null)
    {
        $grades = $this->grades()
            ->bySubject($subjectId)
            ->present();

        if ($period) {
            $grades->whereHas('evaluation', function ($q) use ($period) {
                $q->where('period', $period);
            });
        }

        return $grades->avg('weighted_score') ?? 0;
    }

    public function getGradePoints()
    {
        $totalPoints = 0;
        $totalCoefficients = 0;

        foreach ($this->grades()->present()->get() as $grade) {
            $totalPoints += $grade->weighted_score * $grade->coefficient;
            $totalCoefficients += $grade->coefficient;
        }

        return $totalCoefficients > 0 ? $totalPoints / $totalCoefficients : 0;
    }

    public function getPassingRate()
    {
        $totalGrades = $this->grades()->count();
        if ($totalGrades === 0) return 0;

        $passingGrades = $this->grades()->where('weighted_score', '>=', 10)->count();
        return round(($passingGrades / $totalGrades) * 100, 1);
    }

    public function isPassing()
    {
        return $this->getAverageGrade() >= 10;
    }

    public function getReportCard($academicYearId = null)
    {
        $grades = $this->grades()->with(['evaluation.subject', 'evaluation.teacher']);

        if ($academicYearId) {
            $grades->byAcademicYear($academicYearId);
        }

        $grades = $grades->get()->groupBy(function ($grade) {
            return $grade->evaluation->subject->name;
        });

        $subjects = [];

        foreach ($grades as $subjectName => $subjectGrades) {
            $subjects[$subjectName] = [
                'subject' => $subjectName,
                'grades' => $subjectGrades,
                'average' => $subjectGrades->avg('weighted_score'),
                'teacher' => $subjectGrades->first()->evaluation->teacher->name,
                'coefficient' => $subjectGrades->first()->evaluation->coefficient,
            ];
        }

        $overallAverage = $this->getAverageGrade(null, $academicYearId);

        return [
            'student' => $this,
            'subjects' => $subjects,
            'overall_average' => $overallAverage,
            'is_passing' => $overallAverage >= 10,
            'academic_year' => $academicYearId ? \Modules\Academic\Entities\AcademicYear::find($academicYearId) : null,
        ];
    }
}
