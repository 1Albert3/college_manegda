<?php

namespace App\Models\Lycee;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use App\Models\SchoolYear;

/**
 * Modèle ClassLycee - Base Lycée (school_lycee)
 * 
 * Classes du lycée: 2nde, 1ère, Terminale
 * Avec gestion des séries à partir de la 1ère
 */
class ClassLycee extends Model
{
    use HasUuids;

    protected $connection = 'school_lycee';
    protected $table = 'classes_lycee';

    protected $fillable = [
        'school_year_id',
        'niveau',
        'serie',
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
     * Niveaux disponibles au lycée
     */
    const NIVEAUX = ['2nde', '1ere', '1ère', 'Tle'];

    /**
     * Séries disponibles (à partir de 1ère)
     */
    const SERIES = ['A', 'C', 'D', 'E', 'F', 'G'];

    /**
     * Libellés des séries
     */
    const SERIES_LABELS = [
        'A' => 'Littéraire',
        'C' => 'Sciences Mathématiques',
        'D' => 'Sciences Expérimentales',
        'E' => 'Sciences et Techniques',
        'F' => 'Techniques Industrielles',
        'G' => 'Techniques de Gestion',
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
        return $this->belongsTo(TeacherLycee::class, 'prof_principal_id');
    }

    /**
     * Inscriptions dans cette classe
     */
    public function enrollments()
    {
        return $this->hasMany(EnrollmentLycee::class, 'class_id');
    }

    /**
     * Élèves de la classe (via inscriptions validées)
     */
    public function students()
    {
        return $this->hasManyThrough(
            StudentLycee::class,
            EnrollmentLycee::class,
            'class_id',
            'id',
            'id',
            'student_id'
        )->where('enrollments_lycee.statut', 'validee');
    }

    /**
     * Affectations matières/enseignants (TODO: Implement AssignmentLycee model)
     */
    // public function assignments()
    // {
    //     return $this->hasMany(AssignmentLycee::class, 'class_id');
    // }

    /**
     * Notes
     */
    public function grades()
    {
        return $this->hasMany(GradeLycee::class, 'class_id');
    }

    /**
     * Nom complet de la classe
     */
    public function getFullNameAttribute(): string
    {
        if ($this->serie) {
            return "{$this->niveau} {$this->serie} - {$this->nom}";
        }
        return "{$this->niveau} - {$this->nom}";
    }

    /**
     * Nom de la série en toutes lettres
     */
    public function getSerieNameAttribute(): ?string
    {
        return $this->serie ? (self::SERIES_LABELS[$this->serie] ?? $this->serie) : null;
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
     * Est une classe d'examen (Terminale)
     */
    public function isExamClass(): bool
    {
        return $this->niveau === 'Tle';
    }

    /**
     * Est une classe à série (1ère ou Tle)
     */
    public function isSerieClass(): bool
    {
        return in_array($this->niveau, ['1ere', 'Tle']);
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
     * Scope: par série
     */
    public function scopeBySerie($query, string $serie)
    {
        return $query->where('serie', $serie);
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
     * Scope: terminales
     */
    public function scopeTerminales($query)
    {
        return $query->where('niveau', 'Tle');
    }

    /**
     * Scope: non pleines
     */
    public function scopeNotFull($query)
    {
        return $query->whereRaw('effectif_actuel < seuil_maximum');
    }
}
