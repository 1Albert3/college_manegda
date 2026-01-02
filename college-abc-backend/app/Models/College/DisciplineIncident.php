<?php

namespace App\Models\College;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Modèle DisciplineIncident
 * 
 * Gestion des incidents disciplinaires
 */
class DisciplineIncident extends Model
{
    use HasUuids;

    protected $connection = 'school_college';
    protected $table = 'discipline_incidents';

    protected $fillable = [
        'student_id',
        'class_id',
        'school_year_id',
        'date_incident',
        'heure_incident',
        'lieu',
        'type',
        'gravite',
        'description',
        'circonstances',
        'temoins',
        'signale_par',
        'statut',
    ];

    protected $casts = [
        'date_incident' => 'date',
        'temoins' => 'array',
    ];

    /**
     * Types d'incidents
     */
    const TYPES = [
        'comportement' => 'Mauvais comportement',
        'violence' => 'Violence physique ou verbale',
        'retards_repetes' => 'Retards répétés',
        'absences' => 'Absences injustifiées',
        'tricherie' => 'Fraude aux examens',
        'degradation' => 'Dégradation de matériel',
        'insolence' => 'Insolence envers le personnel',
        'tenue' => 'Non-respect de la tenue',
        'autre' => 'Autre',
    ];

    /**
     * Niveaux de gravité
     */
    const GRAVITES = [
        'mineure' => ['label' => 'Mineure', 'color' => '#fef9c3', 'points' => 1],
        'moyenne' => ['label' => 'Moyenne', 'color' => '#fed7aa', 'points' => 2],
        'grave' => ['label' => 'Grave', 'color' => '#fecaca', 'points' => 4],
        'tres_grave' => ['label' => 'Très grave', 'color' => '#fca5a5', 'points' => 8],
    ];

    /**
     * Élève concerné
     */
    public function student(): BelongsTo
    {
        return $this->belongsTo(StudentCollege::class, 'student_id');
    }

    /**
     * Classe
     */
    public function class(): BelongsTo
    {
        return $this->belongsTo(ClassCollege::class, 'class_id');
    }

    /**
     * Sanctions liées
     */
    public function sanctions(): HasMany
    {
        return $this->hasMany(DisciplineSanction::class, 'incident_id');
    }

    /**
     * Label du type
     */
    public function getTypeNameAttribute(): string
    {
        return self::TYPES[$this->type] ?? $this->type;
    }

    /**
     * Label de gravité
     */
    public function getGraviteInfoAttribute(): array
    {
        return self::GRAVITES[$this->gravite] ?? ['label' => $this->gravite, 'color' => '#e2e8f0', 'points' => 0];
    }

    /**
     * Scope: par élève
     */
    public function scopeForStudent($query, string $studentId)
    {
        return $query->where('student_id', $studentId);
    }

    /**
     * Scope: non traités
     */
    public function scopePending($query)
    {
        return $query->whereIn('statut', ['signale', 'en_cours']);
    }

    /**
     * Scope: par gravité
     */
    public function scopeByGravity($query, string $gravite)
    {
        return $query->where('gravite', $gravite);
    }
}
