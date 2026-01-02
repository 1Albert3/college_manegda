<?php

namespace App\Models\College;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class TeacherSubjectAssignment extends Model
{
    use HasUuids;

    protected $connection = 'school_college';
    protected $table = 'teacher_subject_assignments';

    protected $fillable = [
        'teacher_id',
        'subject_id',
        'class_id',
        'school_year_id',
        'heures_par_semaine',
    ];

    public function teacher()
    {
        return $this->belongsTo(TeacherCollege::class, 'teacher_id');
    }

    public function subject()
    {
        return $this->belongsTo(SubjectCollege::class, 'subject_id');
    }

    public function classRoom()
    {
        return $this->belongsTo(ClassCollege::class, 'class_id');
    }
}
