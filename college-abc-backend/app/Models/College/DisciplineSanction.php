<?php

namespace App\Models\College;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\User;

/**
 * Modèle DisciplineSanction
 * 
 * Gestion des sanctions disciplinaires
 */
class DisciplineSanction extends Model
{
    use HasUuids;

    protected $connection = 'school_college';
    protected $table = 'discipline_sanctions';

    protected $fillable = [
        'incident_id',
        'student_id',
        'school_year_id',
        'type',
        'motif',
        'date_effet',
        'date_fin',
        'duree_jours',
        'parents_notifies',
        'date_notification_parents',
        'mode_notification',
        'decide_par',
        'niveau_decision',
        'observations',
        'appel_fait',
        'resultat_appel',
    ];

    protected $casts = [
        'date_effet' => 'date',
        'date_fin' => 'date',
        'date_notification_parents' => 'datetime',
        'parents_notifies' => 'boolean',
        'appel_fait' => 'boolean',
    ];

    /**
     * Types de sanctions avec gravité
     */
    const TYPES = [
        'avertissement_oral' => [
            'label' => 'Avertissement oral',
            'niveau_min' => 'enseignant',
            'gravite' => 1,
            'notification_obligatoire' => false,
        ],
        'avertissement_ecrit' => [
            'label' => 'Avertissement écrit',
            'niveau_min' => 'censorat',
            'gravite' => 2,
            'notification_obligatoire' => true,
        ],
        'blame' => [
            'label' => 'Blâme',
            'niveau_min' => 'direction',
            'gravite' => 3,
            'notification_obligatoire' => true,
        ],
        'retenue' => [
            'label' => 'Retenue',
            'niveau_min' => 'censorat',
            'gravite' => 2,
            'notification_obligatoire' => true,
        ],
        'travail_interet_general' => [
            'label' => 'Travail d\'intérêt général',
            'niveau_min' => 'direction',
            'gravite' => 3,
            'notification_obligatoire' => true,
        ],
        'exclusion_temporaire' => [
            'label' => 'Exclusion temporaire',
            'niveau_min' => 'direction',
            'gravite' => 4,
            'notification_obligatoire' => true,
        ],
        'exclusion_cours' => [
            'label' => 'Exclusion de cours',
            'niveau_min' => 'enseignant',
            'gravite' => 2,
            'notification_obligatoire' => false,
        ],
        'conseil_discipline' => [
            'label' => 'Passage en conseil de discipline',
            'niveau_min' => 'direction',
            'gravite' => 5,
            'notification_obligatoire' => true,
        ],
        'exclusion_definitive' => [
            'label' => 'Exclusion définitive',
            'niveau_min' => 'conseil',
            'gravite' => 6,
            'notification_obligatoire' => true,
        ],
    ];

    /**
     * Incident associé
     */
    public function incident(): BelongsTo
    {
        return $this->belongsTo(DisciplineIncident::class, 'incident_id');
    }

    /**
     * Élève
     */
    public function student(): BelongsTo
    {
        return $this->belongsTo(StudentCollege::class, 'student_id');
    }

    /**
     * Décideur
     */
    public function decider(): BelongsTo
    {
        return $this->setConnection('school_core')->belongsTo(User::class, 'decide_par');
    }

    /**
     * Obtenir les infos du type
     */
    public function getTypeInfoAttribute(): array
    {
        return self::TYPES[$this->type] ?? ['label' => $this->type, 'gravite' => 0];
    }

    /**
     * Label du type
     */
    public function getTypeNameAttribute(): string
    {
        return self::TYPES[$this->type]['label'] ?? $this->type;
    }

    /**
     * Est une exclusion active
     */
    public function isActiveExclusion(): bool
    {
        if (!in_array($this->type, ['exclusion_temporaire', 'exclusion_definitive'])) {
            return false;
        }

        if ($this->type === 'exclusion_definitive') {
            return true;
        }

        return $this->date_fin && $this->date_fin->isFuture();
    }

    /**
     * Notifier les parents
     */
    public function notifyParents(string $mode = 'sms'): void
    {
        // TODO: Appeler le service de notification
        $this->update([
            'parents_notifies' => true,
            'date_notification_parents' => now(),
            'mode_notification' => $mode,
        ]);
    }

    /**
     * Scope: par type
     */
    public function scopeByType($query, string $type)
    {
        return $query->where('type', $type);
    }

    /**
     * Scope: sanctions actives
     */
    public function scopeActive($query)
    {
        return $query->where(function ($q) {
            $q->whereNull('date_fin')
                ->orWhere('date_fin', '>=', now());
        });
    }

    /**
     * Scope: cette année
     */
    public function scopeCurrentYear($query, string $schoolYearId)
    {
        return $query->where('school_year_id', $schoolYearId);
    }
}
