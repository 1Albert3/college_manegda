<?php

namespace App\Models\MP;

use App\Models\SchoolYear;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Modèle ClassMP - Classes Maternelle/Primaire
 * Base de données: school_maternelle_primaire
 */
class ClassMP extends Model
{
    use HasUuids, SoftDeletes;

    protected $connection = 'school_mp';
    protected $table = 'classes_mp';

    protected $fillable = [
        'school_year_id',
        'niveau',
        'nom',
        'seuil_minimum',
        'seuil_maximum',
        'effectif_actuel',
        'salle',
        'teacher_id',
        'is_active',
    ];

    protected $casts = [
        'seuil_minimum' => 'integer',
        'seuil_maximum' => 'integer',
        'effectif_actuel' => 'integer',
        'is_active' => 'boolean',
    ];

    /**
     * Niveaux disponibles
     */
    const NIVEAU_PS = 'PS';  // Petite Section (Maternelle)
    const NIVEAU_MS = 'MS';  // Moyenne Section (Maternelle)
    const NIVEAU_GS = 'GS';  // Grande Section (Maternelle)
    const NIVEAU_CP = 'CP';
    const NIVEAU_CE1 = 'CE1';
    const NIVEAU_CE2 = 'CE2';
    const NIVEAU_CM1 = 'CM1';
    const NIVEAU_CM2 = 'CM2';

    const NIVEAUX_MATERNELLE = ['PS', 'MS', 'GS'];
    const NIVEAUX_PRIMAIRE = ['CP', 'CE1', 'CE2', 'CM1', 'CM2'];

    /**
     * Vérifier si c'est une classe de maternelle
     */
    public function isMaternelle(): bool
    {
        return in_array($this->niveau, self::NIVEAUX_MATERNELLE);
    }

    /**
     * Vérifier si c'est une classe de primaire
     */
    public function isPrimaire(): bool
    {
        return in_array($this->niveau, self::NIVEAUX_PRIMAIRE);
    }

    /**
     * Vérifier si la classe est pleine (90%+)
     */
    public function isAlmostFull(): bool
    {
        return $this->effectif_actuel >= ($this->seuil_maximum * 0.9);
    }

    /**
     * Vérifier si la classe est complète
     */
    public function isFull(): bool
    {
        return $this->effectif_actuel >= $this->seuil_maximum;
    }

    /**
     * Places disponibles
     */
    public function getAvailableSpotsAttribute(): int
    {
        return max(0, $this->seuil_maximum - $this->effectif_actuel);
    }

    /**
     * Taux de remplissage
     */
    public function getFillRateAttribute(): float
    {
        if ($this->seuil_maximum === 0) return 0;
        return round(($this->effectif_actuel / $this->seuil_maximum) * 100, 2);
    }

    /**
     * Nom complet
     */
    public function getFullNameAttribute(): string
    {
        return $this->nom;
    }

    /**
     * Incrémenter l'effectif
     */
    public function incrementEffectif(): void
    {
        $this->increment('effectif_actuel');
    }

    /**
     * Décrémenter l'effectif
     */
    public function decrementEffectif(): void
    {
        if ($this->effectif_actuel > 0) {
            $this->decrement('effectif_actuel');
        }
    }

    /**
     * Relation avec l'année scolaire (cross-database)
     */
    public function schoolYear()
    {
        return $this->belongsTo(SchoolYear::class);
    }

    /**
     * Relation avec l'enseignant titulaire
     */
    public function teacher()
    {
        return $this->belongsTo(TeacherMP::class, 'teacher_id');
    }

    /**
     * Relation avec les inscriptions
     */
    public function enrollments()
    {
        return $this->hasMany(EnrollmentMP::class, 'class_id');
    }

    /**
     * Élèves inscrits
     */
    public function students()
    {
        return $this->belongsToMany(StudentMP::class, 'enrollments_mp', 'class_id', 'student_id')
            ->wherePivot('statut', 'validee');
    }

    /**
     * Scope: classes actives
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope: par niveau
     */
    public function scopeByNiveau($query, string $niveau)
    {
        return $query->where('niveau', $niveau);
    }

    /**
     * Scope: maternelle
     */
    public function scopeMaternelle($query)
    {
        return $query->whereIn('niveau', self::NIVEAUX_MATERNELLE);
    }

    /**
     * Scope: primaire
     */
    public function scopePrimaire($query)
    {
        return $query->whereIn('niveau', self::NIVEAUX_PRIMAIRE);
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

    /**
     * Scope: avec places disponibles
     */
    public function scopeWithAvailableSpots($query)
    {
        return $query->whereRaw('effectif_actuel < seuil_maximum');
    }
}
