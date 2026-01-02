<?php

namespace App\Models\College;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use App\Models\SchoolYear;

/**
 * Modèle ClassCollege - Base Collège (school_college)
 * 
 * Classes du collège: 6ème, 5ème, 4ème, 3ème
 */
class ClassCollege extends Model
{
    use HasUuids;

    protected $connection = 'school_college';
    protected $table = 'classes_college';

    protected $fillable = [
        'school_year_id',
        'niveau',
        'nom',
        'seuil_minimum',
        'seuil_maximum',
        'effectif_actuel',
        'salle',
        'prof_principal_id',
        'is_active',
    ];

    protected $casts = [
        'seuil_minimum' => 'integer',
        'seuil_maximum' => 'integer',
        'effectif_actuel' => 'integer',
        'is_active' => 'boolean',
    ];

    /**
     * Niveaux disponibles au collège
     */
    const NIVEAUX = ['6eme', '5eme', '4eme', '3eme'];

    /**
     * Seuils par défaut par niveau (selon normes burkinabè)
     */
    const SEUILS_DEFAULT = [
        '6eme' => ['min' => 30, 'max' => 50],
        '5eme' => ['min' => 30, 'max' => 50],
        '4eme' => ['min' => 30, 'max' => 50],
        '3eme' => ['min' => 30, 'max' => 50],
    ];

    /**
     * Année scolaire
     */
    public function schoolYear()
    {
        return $this->belongsTo(SchoolYear::class);
    }

    /**
     * Professeur principal
     */
    public function profPrincipal()
    {
        return $this->belongsTo(TeacherCollege::class, 'prof_principal_id');
    }

    /**
     * Inscriptions dans cette classe
     */
    public function enrollments()
    {
        return $this->hasMany(EnrollmentCollege::class, 'class_id');
    }

    /**
     * Élèves de la classe (via inscriptions validées)
     */
    public function students()
    {
        return $this->hasManyThrough(
            StudentCollege::class,
            EnrollmentCollege::class,
            'class_id',
            'id',
            'id',
            'student_id'
        )->where('enrollments_college.statut', 'validee');
    }

    /**
     * Affectations matières/enseignants
     */
    public function assignments()
    {
        return $this->hasMany(AssignmentCollege::class, 'class_id');
    }

    /**
     * Notes
     */
    public function grades()
    {
        return $this->hasMany(GradeCollege::class, 'class_id');
    }

    /**
     * Nom complet de la classe
     */
    public function getFullNameAttribute(): string
    {
        return "{$this->niveau} - {$this->nom}";
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
        if ($this->seuil_maximum <= 0) return 0;
        return round(($this->effectif_actuel / $this->seuil_maximum) * 100, 1);
    }

    /**
     * Est pleine
     */
    public function isFullAttribute(): bool
    {
        return $this->effectif_actuel >= $this->seuil_maximum;
    }

    /**
     * Est presque pleine (>= 90%)
     */
    public function isAlmostFullAttribute(): bool
    {
        return $this->effectif_actuel >= ($this->seuil_maximum * 0.9);
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
     * Recalculer l'effectif depuis les inscriptions
     */
    public function recalculateEffectif(): void
    {
        $count = $this->enrollments()
            ->where('statut', 'validee')
            ->where('school_year_id', SchoolYear::current()?->id)
            ->count();

        $this->update(['effectif_actuel' => $count]);
    }

    /**
     * Scope: actives uniquement
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
     * Scope: année courante
     */
    public function scopeCurrentYear($query)
    {
        $currentYearId = SchoolYear::current()?->id;
        return $query->where('school_year_id', $currentYearId);
    }

    /**
     * Scope: non pleines
     */
    public function scopeNotFull($query)
    {
        return $query->whereRaw('effectif_actuel < seuil_maximum');
    }
}
