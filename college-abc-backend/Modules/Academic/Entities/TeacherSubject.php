<?php

namespace Modules\Academic\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Pivot;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class TeacherSubject extends Pivot
{
    use HasFactory;

    protected $table = 'teacher_subject';

    protected $fillable = [
        'teacher_id', 'subject_id', 'academic_year_id'
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    // Relations
    public function teacher()
    {
        return $this->belongsTo(\Modules\Core\Entities\User::class, 'teacher_id');
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

    public function scopeBySubject($query, int $subjectId)
    {
        return $query->where('subject_id', $subjectId);
    }

    public function scopeByTeacher($query, int $teacherId)
    {
        return $query->where('teacher_id', $teacherId);
    }
}
