<?php

namespace App\Models\College;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\User;

/**
 * Modèle GradeCollege - Notes Collège
 * Base: school_college
 */
class GradeCollege extends Model
{
    use HasUuids, SoftDeletes;

    protected $connection = 'school_college';
    protected $table = 'grades_college';

    protected $fillable = [
        'school_year_id',
        'class_id',
        'student_id',
        'subject_id',
        'teacher_id',
        'trimestre', // 1, 2, 3
        'type_evaluation', // 'devoir', 'compo'
        'note_sur', // often 20, sometimes 10
        'note_obtenue',
        'note_sur_20',
        'coefficient', // Surcharge possible
        'appreciation',
        'date_evaluation',
        'is_published',
        'published_at',
        'published_by',
        'recorded_by'
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

    public function student()
    {
        return $this->belongsTo(StudentCollege::class, 'student_id');
    }

    public function subject()
    {
        return $this->belongsTo(SubjectCollege::class, 'subject_id');
    }

    public function class()
    {
        return $this->belongsTo(ClassCollege::class, 'class_id');
    }
}
