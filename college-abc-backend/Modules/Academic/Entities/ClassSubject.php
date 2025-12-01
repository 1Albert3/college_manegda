<?php

namespace Modules\Academic\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Pivot;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class ClassSubject extends Pivot
{
    use HasFactory;

    protected $table = 'class_subject';

    protected $fillable = [
        'class_id', 'subject_id', 'academic_year_id',
        'weekly_hours', 'coefficient'
    ];

    protected $casts = [
        'weekly_hours' => 'integer',
        'coefficient' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    // Relations
    public function class()
    {
        return $this->belongsTo(ClassRoom::class, 'class_id');
    }

    public function subject()
    {
        return $this->belongsTo(Subject::class);
    }

    public function academicYear()
    {
        return $this->belongsTo(AcademicYear::class);
    }

    // Scopes
    public function scopeCurrentYear($query)
    {
        return $query->where('academic_year_id', AcademicYear::getCurrentYear()?->id);
    }

    public function scopeByClass($query, int $classId)
    {
        return $query->where('class_id', $classId);
    }

    public function scopeBySubject($query, int $subjectId)
    {
        return $query->where('subject_id', $subjectId);
    }

    public function scopeByAcademicYear($query, int $yearId)
    {
        return $query->where('academic_year_id', $yearId);
    }

    // Accessors
    public function getTotalHoursAttribute()
    {
        return $this->weekly_hours * 36; // Approximation: 36 semaines par année
    }

    public function getSubjectNameAttribute()
    {
        return $this->subject?->name;
    }

    public function getClassNameAttribute()
    {
        return $this->class?->name;
    }
}
