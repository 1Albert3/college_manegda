<?php

namespace Modules\Grade\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Traits\HasUuid;
use App\Traits\Searchable;
use Carbon\Carbon;

class Evaluation extends Model
{
    use HasFactory, HasUuid, Searchable;

    protected $fillable = [
        'name', 'code', 'description', 'type', 'period', 'coefficient',
        'weight_percentage', 'academic_year_id', 'subject_id', 'class_id',
        'teacher_id', 'evaluation_date', 'status', 'maximum_score', 'minimum_score',
        'grading_criteria', 'comments'
    ];

    protected $casts = [
        'evaluation_date' => 'date',
        'maximum_score' => 'decimal:2',
        'minimum_score' => 'decimal:2',
        'weight_percentage' => 'decimal:2',
        'grading_criteria' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    protected $searchable = ['name', 'code', 'description'];

    // Relations
    public function academicYear()
    {
        return $this->belongsTo(\Modules\Academic\Entities\AcademicYear::class);
    }

    public function subject()
    {
        return $this->belongsTo(\Modules\Academic\Entities\Subject::class);
    }

    public function class()
    {
        return $this->belongsTo(\Modules\Academic\Entities\ClassRoom::class, 'class_id');
    }

    public function teacher()
    {
        return $this->belongsTo(\Modules\Core\Entities\User::class, 'teacher_id');
    }

    public function grades()
    {
        return $this->hasMany(Grade::class);
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('status', 'ongoing')->orWhere('status', 'completed');
    }

    public function scopeByType($query, $type)
    {
        return $query->where('type', $type);
    }

    public function scopeByPeriod($query, $period)
    {
        return $query->where('period', $period);
    }

    public function scopeUpcoming($query)
    {
        return $query->where('evaluation_date', '>=', now()->toDateString());
    }

    public function scopeCurrentYear($query)
    {
        return $query->whereHas('academicYear', fn($q) => $q->current());
    }

    // Accessors & Methods
    public function getIsCompletedAttribute()
    {
        return $this->status === 'completed';
    }

    public function getIsOngoingAttribute()
    {
        return $this->status === 'ongoing';
    }

    public function getIsPastAttribute()
    {
        return Carbon::parse($this->evaluation_date)->isPast();
    }

    public function getDaysUntilAttribute()
    {
        return now()->diffInDays(Carbon::parse($this->evaluation_date), false);
    }

    public function getHasGradesAttribute()
    {
        return $this->grades()->exists();
    }

    public function getCompletionPercentageAttribute()
    {
        $totalStudents = $this->class->enrollments()->whereHas('academicYear', fn($q) => $q->current())->count();
        $gradedStudents = $this->grades()->count();

        return $totalStudents > 0 ? round(($gradedStudents / $totalStudents) * 100, 1) : 0;
    }

    public function complete()
    {
        $this->update(['status' => 'completed']);
        return $this;
    }

    public function cancel()
    {
        $this->update(['status' => 'cancelled']);
        return $this;
    }

    public function start()
    {
        $this->update(['status' => 'ongoing']);
        return $this;
    }

    public function getAverageGrade()
    {
        return $this->grades()->whereNot('is_absent', true)->avg('score');
    }

    public function getGradeDistribution()
    {
        return [
            'A+' => $this->grades()->where('grade_letter', 'A+')->count(),
            'A' => $this->grades()->where('grade_letter', 'A')->count(),
            'B+' => $this->grades()->where('grade_letter', 'B+')->count(),
            'B' => $this->grades()->where('grade_letter', 'B')->count(),
            'C+' => $this->grades()->where('grade_letter', 'C+')->count(),
            'C' => $this->grades()->where('grade_letter', 'C')->count(),
            'D+' => $this->grades()->where('grade_letter', 'D+')->count(),
            'D' => $this->grades()->where('grade_letter', 'D')->count(),
            'F' => $this->grades()->where('grade_letter', 'F')->count(),
        ];
    }
}
