<?php

namespace Modules\Gradebook\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Modules\Academic\Entities\Subject;
use Modules\Academic\Entities\ClassRoom;
use Modules\Academic\Entities\Semester;
use Modules\Academic\Entities\AcademicYear;
use Modules\Core\Entities\User;

class Evaluation extends Model
{
    use HasFactory;

    protected $fillable = [
        'subject_id', 'class_room_id', 'teacher_id', 'semester_id',
        'academic_year_id', 'title', 'type', 'max_score',
        'coefficient', 'date', 'description',
    ];

    protected $casts = ['date' => 'date', 'max_score' => 'decimal:2', 'coefficient' => 'decimal:2'];

    // Relations
    public function subject() { return $this->belongsTo(Subject::class); }
    public function classRoom() { return $this->belongsTo(ClassRoom::class); }
    public function teacher() { return $this->belongsTo(User::class, 'teacher_id'); }
    public function semester() { return $this->belongsTo(Semester::class); }
    public function academicYear() { return $this->belongsTo(AcademicYear::class); }
    public function grades() { return $this->hasMany(Grade::class); }

    // Scopes
    public function scopeByClass($q, $id) { return $q->where('class_room_id', $id); }
    public function scopeBySubject($q, $id) { return $q->where('subject_id', $id); }
    public function scopeBySemester($q, $id) { return $q->where('semester_id', $id); }
    public function scopeByType($q, $type) { return $q->where('type', $type); }

    // Accessors
    public function getTypeLabelAttribute()
    {
        return ['exam' => 'Examen', 'test' => 'Interrogation', 'quiz' => 'Quiz', 
                'homework' => 'Devoir', 'participation' => 'Participation'][$this->type] ?? $this->type;
    }

    public function getClassAverageAttribute()
    {
        return $this->grades()->avg('score') ?? 0;
    }

    public function getCompletionRateAttribute()
    {
        $total = $this->classRoom->students()->count();
        $graded = $this->grades()->count();
        return $total > 0 ? round(($graded / $total) * 100, 2) : 0;
    }
}
