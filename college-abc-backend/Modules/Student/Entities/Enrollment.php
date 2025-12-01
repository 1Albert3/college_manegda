<?php

namespace Modules\Student\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Enrollment extends Model
{
    use HasFactory;

    protected $fillable = [
        'student_id', 'academic_year_id', 'class_id',
        'enrollment_date', 'status', 'discount_percentage', 'notes'
    ];

    protected $casts = [
        'enrollment_date' => 'date',
        'discount_percentage' => 'decimal:2',
    ];

    // Relations
    public function student()
    {
        return $this->belongsTo(Student::class);
    }

    // public function academicYear()
    // {
    //     return $this->belongsTo(\Modules\Academic\Entities\AcademicYear::class);
    // }

    // public function class()
    // {
    //     return $this->belongsTo(\Modules\Academic\Entities\ClassRoom::class, 'class_id');
    // }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function scopeByAcademicYear($query, $yearId)
    {
        return $query->where('academic_year_id', $yearId);
    }

    public function scopeByClass($query, $classId)
    {
        return $query->where('class_id', $classId);
    }
}
