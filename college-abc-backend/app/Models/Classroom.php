<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Classroom extends Model
{
    protected $fillable = [
        'name',
        'level',
        'capacity',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function enrollments()
    {
        return $this->hasMany(Enrollment::class);
    }

    public function students()
    {
        return $this->hasManyThrough(Student::class, Enrollment::class, 'classroom_id', 'id', 'id', 'student_id')
            ->where('enrollments.status', 'active');
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}