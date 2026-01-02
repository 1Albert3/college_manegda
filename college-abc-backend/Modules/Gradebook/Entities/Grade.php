<?php

namespace Modules\Gradebook\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Modules\Student\Entities\Student;
use Modules\Core\Entities\User;

class Grade extends Model
{
    use HasFactory;

    protected $fillable = [
        'evaluation_id', 'student_id', 'score', 'weighted_score',
        'comment', 'graded_by', 'graded_at',
    ];

    protected $casts = ['score' => 'decimal:2', 'weighted_score' => 'decimal:2', 'graded_at' => 'datetime'];

    protected static function boot()
    {
        parent::boot();
        static::creating(function ($grade) {
            $grade->weighted_score = $grade->score * $grade->evaluation->coefficient;
            $grade->graded_at = now();
        });
    }

    // Relations
    public function evaluation() { return $this->belongsTo(Evaluation::class); }
    public function student() { return $this->belongsTo(Student::class); }
    public function gradedBy() { return $this->belongsTo(User::class, 'graded_by'); }

    // Scopes
    public function scopeByStudent($q, $id) { return $q->where('student_id', $id); }
    public function scopeByEvaluation($q, $id) { return $q->where('evaluation_id', $id); }
    public function scopePassing($q) { return $q->where('score', '>=', 10); }
    public function scopeFailing($q) { return $q->where('score', '<', 10); }

    // Accessors
    public function getPercentageAttribute() { return ($this->score / $this->evaluation->max_score) * 100; }
    public function getLetterGradeAttribute()
    {
        $p = $this->percentage;
        if ($p >= 90) return 'A';
        if ($p >= 80) return 'B';
        if ($p >= 70) return 'C';
        if ($p >= 60) return 'D';
        return 'F';
    }
    public function getIsPassingAttribute() { return $this->score >= 10; }
}
