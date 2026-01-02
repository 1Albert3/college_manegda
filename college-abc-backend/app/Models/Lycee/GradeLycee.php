<?php

namespace App\Models\Lycee;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

use App\Models\User;

/**
 * Modèle GradeLycee - Notes Lycée
 * Base: school_lycee
 */
class GradeLycee extends Model
{
    use HasUuids;

    protected $connection = 'school_lycee';
    protected $table = 'grades_lycee';

    protected $fillable = [
        'school_year_id',
        'class_id',
        'student_id',
        'subject_id',
        'teacher_id',
        'trimestre',
        'type_evaluation',
        'note_sur',
        'note_obtenue',
        'note_sur_20',
        'coefficient',
        'commentaire',
        'date_evaluation',
        'is_published',
        'published_at',
        'published_by',
        'recorded_by',
    ];

    /**
     * Auto-fill fields if missing on creation
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($grade) {
            $grade->note_sur = $grade->note_sur ?? 20;
            // Si note_obtenue manque, on assume que note_sur_20 est la note obtenue (sur 20)
            $grade->note_obtenue = $grade->note_obtenue ?? $grade->note_sur_20;

            // Auteur par défaut
            $grade->recorded_by = $grade->recorded_by ?? \Illuminate\Support\Facades\Auth::id() ?? '00000000-0000-0000-0000-000000000000';
        });
    }

    protected $casts = [
        'note_sur_20' => 'float',
        'coefficient' => 'integer',
        'date_evaluation' => 'date',
        'is_published' => 'boolean',
        'published_at' => 'datetime',
    ];

    // Relations
    public function student()
    {
        return $this->belongsTo(StudentLycee::class, 'student_id');
    }

    public function subject()
    {
        return $this->belongsTo(SubjectLycee::class, 'subject_id');
    }

    public function class()
    {
        return $this->belongsTo(ClassLycee::class, 'class_id');
    }

    public function teacher()
    {
        return $this->belongsTo(TeacherLycee::class, 'teacher_id');
    }

    public function publisher()
    {
        return $this->belongsTo(User::class, 'published_by')->onConnection('school_core');
    }

    // Scopes
    public function scopePublished($query)
    {
        return $query->where('is_published', true);
    }

    public function scopeForStudent($query, $studentId)
    {
        return $query->where('student_id', $studentId);
    }
}
