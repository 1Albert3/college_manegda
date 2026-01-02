<?php

namespace Modules\Grade\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\Searchable;

class Grade extends Model
{
    use HasFactory, SoftDeletes, Searchable;

    protected $fillable = [
        'student_id',
        'evaluation_id',
        'score',
        'coefficient',
        'weighted_score',
        'grade_letter',
        'is_absent',
        'comments',
        'recorded_at',
        'recorded_by'
    ];

    protected $casts = [
        'score' => 'decimal:2',
        'coefficient' => 'decimal:2',
        'weighted_score' => 'decimal:2',
        'is_absent' => 'boolean',
        'recorded_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    protected $searchable = ['comments'];

    // Relations
    public function student()
    {
        return $this->belongsTo(\Modules\Student\Entities\Student::class);
    }

    public function evaluation()
    {
        return $this->belongsTo(Evaluation::class);
    }

    public function recorder()
    {
        return $this->belongsTo(\Modules\Core\Entities\User::class, 'recorded_by');
    }

    // Scopes
    public function scopeByStudent($query, $studentId)
    {
        return $query->where('student_id', $studentId);
    }

    public function scopeByEvaluation($query, $evaluationId)
    {
        return $query->where('evaluation_id', $evaluationId);
    }

    public function scopePresent($query)
    {
        return $query->where('is_absent', false);
    }

    public function scopeAbsent($query)
    {
        return $query->where('is_absent', true);
    }

    public function scopeByGradeLetter($query, $letter)
    {
        return $query->where('grade_letter', $letter);
    }

    public function scopeByAcademicYear($query, $yearId)
    {
        return $query->whereHas('evaluation', function ($q) use ($yearId) {
            $q->where('academic_year_id', $yearId);
        });
    }

    public function scopeBySubject($query, $subjectId)
    {
        return $query->whereHas('evaluation', function ($q) use ($subjectId) {
            $q->where('subject_id', $subjectId);
        });
    }

    public function scopeByClass($query, $classId)
    {
        return $query->whereHas('evaluation', function ($q) use ($classId) {
            $q->where('class_room_id', $classId);
        });
    }

    public function scopeCurrentYear($query)
    {
        return $query->whereHas('evaluation.academicYear', fn($q) => $q->current());
    }

    // Accessors & Methods
    public function getIsPassingAttribute()
    {
        if ($this->is_absent) {
            return false;
        }

        return $this->weighted_score >= 10;
    }

    public function getGradeColorAttribute()
    {
        if ($this->is_absent) return 'gray';

        $score = $this->weighted_score;

        if ($score >= 16) return 'excellent'; // A+, A
        if ($score >= 14) return 'very-good'; // B+, B
        if ($score >= 12) return 'good'; // C+, C
        if ($score >= 10) return 'passing'; // D+, D
        return 'failing'; // F
    }

    public function getGradeStatusAttribute()
    {
        if ($this->is_absent) return 'absent';

        $score = $this->weighted_score;

        if ($score >= 16) return 'excellent';
        if ($score >= 14) return 'very-good';
        if ($score >= 12) return 'good';
        if ($score >= 10) return 'passing';
        return 'failing';
    }

    public function getFormattedScoreAttribute()
    {
        return number_format($this->score, 2, ',', ' ');
    }

    public function getFormattedWeightedScoreAttribute()
    {
        return number_format($this->weighted_score, 2, ',', ' ');
    }

    // Convert score to letter grade
    public static function scoreToLetterGrade($score)
    {
        if ($score >= 16) return 'A+';
        if ($score >= 15) return 'A';
        if ($score >= 14) return 'B+';
        if ($score >= 13) return 'B';
        if ($score >= 12) return 'C+';
        if ($score >= 11) return 'C';
        if ($score >= 10) return 'D+';
        if ($score >= 8) return 'D';
        return 'F';
    }

    // Calculate weighted score
    public function calculateWeightedScore()
    {
        $this->weighted_score = $this->score * $this->coefficient * ($this->evaluation->weight_percentage / 100);
        return $this->weighted_score;
    }

    // Set grade letter based on score
    public function setGradeLetter()
    {
        if (!$this->is_absent) {
            $this->grade_letter = self::scoreToLetterGrade($this->weighted_score);
        }
        return $this->grade_letter;
    }

    // Boot method to auto-calculate scores
    protected static function boot()
    {
        parent::boot();

        static::saving(function ($grade) {
            $grade->calculateWeightedScore();
            $grade->setGradeLetter();
        });
    }
}
