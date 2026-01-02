<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Schedule extends Model
{
    protected $fillable = [
        'class_room_id',
        'subject_id',
        'teacher_id',
        'academic_year_id',
        'day_of_week',
        'start_time',
        'end_time',
        'room',
        'notes',
    ];

    protected $casts = [
        'start_time' => 'string',
        'end_time' => 'string',
    ];

    /**
     * Relation avec la classe (ClassRoom)
     */
    public function classRoom(): BelongsTo
    {
        return $this->belongsTo(Classroom::class, 'class_room_id');
    }

    /**
     * Relation avec la matière (Subject)
     */
    public function subject(): BelongsTo
    {
        return $this->belongsTo(Subject::class);
    }

    /**
     * Relation avec l'enseignant (User)
     */
    public function teacher(): BelongsTo
    {
        return $this->belongsTo(User::class, 'teacher_id');
    }

    /**
     * Relation avec l'année académique
     */
    public function academicYear(): BelongsTo
    {
        return $this->belongsTo(SchoolYear::class);
    }
}
