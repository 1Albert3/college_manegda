<?php

namespace App\Models\College;

use App\Models\SchoolYear;
use App\Models\User;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

/**
 * Modèle EnrollmentCollege - Inscriptions Collège
 * Base de données: school_college
 */
class EnrollmentCollege extends Model
{
    use HasUuids;

    protected $connection = 'school_college';
    protected $table = 'enrollments_college';

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
        // Note: montant_paye, solde_restant, prochaine_echeance not in migration
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
        'montant_paye' => 'decimal:2',
        'solde_restant' => 'decimal:2',
        'prochaine_echeance' => 'date',
        'a_bourse' => 'boolean',
    ];

    /**
     * Statuts d'inscription
     */
    const STATUT_EN_ATTENTE = 'en_attente';
    const STATUT_VALIDEE = 'validee';
    const STATUT_REFUSEE = 'refusee';

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
                'pourcentage_bourse',
                'montant_paye'
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
        if ($this->mode_paiement === 'comptant') {
            $reduction += $this->total_a_payer * 0.05;
        }

        $this->montant_final = max(0, $this->total_a_payer - $reduction);

        // Note: solde_restant column doesn't exist in migration, commenting out
        // $this->solde_restant = max(0, $this->montant_final - ($this->montant_paye ?? 0));
    }

    /**
     * Relation avec l'élève
     */
    public function student()
    {
        return $this->belongsTo(StudentCollege::class, 'student_id');
    }

    /**
     * Relation avec la classe
     */
    public function class()
    {
        return $this->belongsTo(ClassCollege::class, 'class_id');
    }

    /**
     * Relation avec l'année scolaire
     */
    public function schoolYear()
    {
        return $this->belongsTo(SchoolYear::class);
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
