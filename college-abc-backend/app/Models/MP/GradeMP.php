<?php

namespace App\Models\MP;

use App\Models\SchoolYear;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

/**
 * Modèle GradeMP - Notes Maternelle/Primaire
 * Base de données: school_maternelle_primaire
 * 
 * Types d'évaluation selon cahier des charges:
 * - IO: Interrogation Orale (/10)
 * - DV: Devoir (/20)
 * - CP: Composition (/100)
 * - TP: Travaux Pratiques (/20)
 */
class GradeMP extends Model
{
    use HasUuids;

    protected $connection = 'school_mp';
    protected $table = 'grades_mp';

    protected $fillable = [
        'student_id',
        'subject_id',
        'class_id',
        'school_year_id',
        'trimestre',
        'type_evaluation',
        'note_sur',
        'note_obtenue',
        'note_sur_20',
        'date_evaluation',
        'commentaire',
        'is_published',
        'published_at',
        'recorded_by',
    ];

    protected $casts = [
        'date_evaluation' => 'date',
        'published_at' => 'datetime',
        'note_sur' => 'decimal:2',
        'note_obtenue' => 'decimal:2',
        'note_sur_20' => 'decimal:2',
        'is_published' => 'boolean',
    ];

    /**
     * Types d'évaluation
     */
    const TYPE_IO = 'IO'; // Interrogation Orale /10
    const TYPE_DV = 'DV'; // Devoir /20
    const TYPE_CP = 'CP'; // Composition /100
    const TYPE_TP = 'TP'; // Travaux Pratiques /20

    /**
     * Barèmes par type
     */
    const BAREMES = [
        self::TYPE_IO => 10,
        self::TYPE_DV => 20,
        self::TYPE_CP => 100,
        self::TYPE_TP => 20,
    ];

    /**
     * Boot du modèle
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($grade) {
            $grade->calculateNoteSur20();
        });

        static::updating(function ($grade) {
            if ($grade->isDirty(['note_obtenue', 'note_sur'])) {
                $grade->calculateNoteSur20();
            }
        });
    }

    /**
     * Convertir la note sur 20
     */
    public function calculateNoteSur20(): void
    {
        if ($this->note_sur > 0) {
            $this->note_sur_20 = round(($this->note_obtenue / $this->note_sur) * 20, 2);
        } else {
            $this->note_sur_20 = 0;
        }
    }

    /**
     * Publier la note (verrouillage)
     */
    public function publish(): void
    {
        $this->is_published = true;
        $this->published_at = now();
        $this->save();
    }

    /**
     * Vérifier si la note peut être modifiée
     */
    public function isEditable(): bool
    {
        return !$this->is_published;
    }

    /**
     * Relation avec l'élève
     */
    public function student()
    {
        return $this->belongsTo(StudentMP::class, 'student_id');
    }

    /**
     * Relation avec la matière
     */
    public function subject()
    {
        return $this->belongsTo(SubjectMP::class, 'subject_id');
    }

    /**
     * Relation avec la classe
     */
    public function class()
    {
        return $this->belongsTo(ClassMP::class, 'class_id');
    }

    /**
     * Relation avec l'année scolaire
     */
    public function schoolYear()
    {
        return $this->belongsTo(SchoolYear::class);
    }

    /**
     * Scope: notes publiées
     */
    public function scopePublished($query)
    {
        return $query->where('is_published', true);
    }

    /**
     * Scope: par trimestre
     */
    public function scopeByTrimestre($query, string $trimestre)
    {
        return $query->where('trimestre', $trimestre);
    }

    /**
     * Scope: par type
     */
    public function scopeByType($query, string $type)
    {
        return $query->where('type_evaluation', $type);
    }

    /**
     * Scope: année courante
     */
    public function scopeCurrentYear($query)
    {
        $currentYear = SchoolYear::current();
        if ($currentYear) {
            return $query->where('school_year_id', $currentYear->id);
        }
        return $query;
    }
}
