<?php

namespace App\Models\MP;

use App\Models\SchoolYear;
use App\Models\User;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

/**
 * Modèle EnrollmentMP - Inscriptions Maternelle/Primaire
 * Base de données: school_maternelle_primaire
 * 
 * Workflow: Secrétariat → Direction → Comptabilité
 */
class EnrollmentMP extends Model
{
    use HasUuids;

    protected $connection = 'school_mp';
    protected $table = 'enrollments_mp';

    protected $fillable = [
        'student_id',
        'class_id',
        'school_year_id',
        'regime',
        'date_inscription',
        'statut',
        'validated_by',
        'validated_at',
        'motif_refus',
        // Financier
        'frais_scolarite',
        'frais_cantine',
        'frais_activites',
        'frais_inscription',
        'total_a_payer',
        'mode_paiement',
        'a_bourse',
        'montant_bourse',
        'pourcentage_bourse',
        'type_bourse',
        'montant_final',
    ];

    protected $casts = [
        'date_inscription' => 'date',
        'validated_at' => 'datetime',
        'frais_scolarite' => 'decimal:2',
        'frais_cantine' => 'decimal:2',
        'frais_activites' => 'decimal:2',
        'frais_inscription' => 'decimal:2',
        'total_a_payer' => 'decimal:2',
        'montant_bourse' => 'decimal:2',
        'pourcentage_bourse' => 'decimal:2',
        'montant_final' => 'decimal:2',
        'a_bourse' => 'boolean',
    ];

    /**
     * Statuts d'inscription
     */
    const STATUT_EN_ATTENTE = 'en_attente';
    const STATUT_VALIDEE = 'validee';
    const STATUT_REFUSEE = 'refusee';

    /**
     * Régimes
     */
    const REGIME_INTERNE = 'interne';
    const REGIME_DEMI_PENSIONNAIRE = 'demi_pensionnaire';
    const REGIME_EXTERNE = 'externe';

    /**
     * Modes de paiement
     */
    const MODE_COMPTANT = 'comptant';
    const MODE_TRANCHES = 'tranches_3';

    /**
     * Boot du modèle - Calcul automatique des montants
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($enrollment) {
            $enrollment->calculateTotals();
        });

        static::updating(function ($enrollment) {
            if ($enrollment->isDirty([
                'frais_scolarite',
                'frais_cantine',
                'frais_activites',
                'frais_inscription',
                'montant_bourse',
                'pourcentage_bourse'
            ])) {
                $enrollment->calculateTotals();
            }
        });
    }

    /**
     * Calculer les totaux
     */
    public function calculateTotals(): void
    {
        $this->total_a_payer =
            ($this->frais_scolarite ?? 0) +
            ($this->frais_cantine ?? 0) +
            ($this->frais_activites ?? 0) +
            ($this->frais_inscription ?? 0);

        // Appliquer la bourse
        $reduction = 0;
        if ($this->a_bourse) {
            if ($this->montant_bourse > 0) {
                $reduction = $this->montant_bourse;
            } elseif ($this->pourcentage_bourse > 0) {
                $reduction = $this->total_a_payer * ($this->pourcentage_bourse / 100);
            }
        }

        // Réduction pour paiement comptant (5%)
        if ($this->mode_paiement === self::MODE_COMPTANT) {
            $reduction += $this->total_a_payer * 0.05;
        }

        $this->montant_final = max(0, $this->total_a_payer - $reduction);
    }

    /**
     * Valider l'inscription
     */
    public function validate(User $validator): void
    {
        $this->statut = self::STATUT_VALIDEE;
        $this->validated_by = $validator->id;
        $this->validated_at = now();
        $this->save();

        // Incrémenter l'effectif de la classe
        $this->class->incrementEffectif();
    }

    /**
     * Refuser l'inscription
     */
    public function reject(string $motif): void
    {
        $this->statut = self::STATUT_REFUSEE;
        $this->motif_refus = $motif;
        $this->save();
    }

    /**
     * Vérifier si en attente
     */
    public function isPending(): bool
    {
        return $this->statut === self::STATUT_EN_ATTENTE;
    }

    /**
     * Vérifier si validée
     */
    public function isValidated(): bool
    {
        return $this->statut === self::STATUT_VALIDEE;
    }

    /**
     * Vérifier si refusée
     */
    public function isRejected(): bool
    {
        return $this->statut === self::STATUT_REFUSEE;
    }

    /**
     * Relation avec l'élève
     */
    public function student()
    {
        return $this->belongsTo(StudentMP::class, 'student_id');
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
     * Relation avec le validateur
     */
    public function validator()
    {
        return $this->belongsTo(User::class, 'validated_by');
    }

    /**
     * Scope: en attente
     */
    public function scopePending($query)
    {
        return $query->where('statut', self::STATUT_EN_ATTENTE);
    }

    /**
     * Scope: validées
     */
    public function scopeValidated($query)
    {
        return $query->where('statut', self::STATUT_VALIDEE);
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
