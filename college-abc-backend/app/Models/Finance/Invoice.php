<?php

namespace App\Models\Finance;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use App\Models\SchoolYear;
use App\Models\User;

/**
 * Modèle Invoice - Factures
 * 
 * Gestion des factures pour les inscriptions et autres frais
 */
class Invoice extends Model
{
    use HasUuids;

    // protected $connection = 'school_core';
    protected $table = 'invoices';

    protected $fillable = [
        'number',
        'student_id',
        'student_database',
        'enrollment_id',
        'school_year_id',
        'type',
        'description',
        'montant_ht',
        'montant_ttc',
        'montant_paye',
        'solde',
        'statut',
        'date_emission',
        'date_echeance',
        'notes',
        'created_by',
    ];

    protected $casts = [
        'montant_ht' => 'decimal:2',
        'montant_ttc' => 'decimal:2',
        'montant_paye' => 'decimal:2',
        'solde' => 'decimal:2',
        'date_emission' => 'date',
        'date_echeance' => 'date',
    ];

    /**
     * Types de factures
     */
    const TYPES = [
        'inscription' => 'Inscription',
        'scolarite' => 'Frais de scolarité',
        'cantine' => 'Cantine',
        'transport' => 'Transport',
        'fournitures' => 'Fournitures',
        'autre' => 'Autre',
    ];

    /**
     * Statuts
     */
    const STATUTS = [
        'brouillon' => 'Brouillon',
        'emise' => 'Émise',
        'partiellement_payee' => 'Partiellement payée',
        'payee' => 'Payée',
        'annulee' => 'Annulée',
    ];

    /**
     * Générer le numéro de facture
     */
    protected static function booted()
    {
        static::creating(function ($invoice) {
            if (empty($invoice->number)) {
                $year = date('Y');
                $lastInvoice = static::whereYear('created_at', $year)
                    ->orderByDesc('created_at')
                    ->first();

                $nextNumber = 1;
                if ($lastInvoice && preg_match('/FAC-\d{4}-(\d+)/', $lastInvoice->number, $matches)) {
                    $nextNumber = intval($matches[1]) + 1;
                }

                $invoice->number = sprintf('FAC-%s-%05d', $year, $nextNumber);
            }

            // Calculer le solde initial
            if ($invoice->solde === null) {
                $invoice->solde = $invoice->montant_ttc - ($invoice->montant_paye ?? 0);
            }
        });
    }

    /**
     * Paiements associés
     */
    public function payments()
    {
        return $this->hasMany(Payment::class, 'invoice_id');
    }

    /**
     * Année scolaire
     */
    public function schoolYear()
    {
        return $this->belongsTo(SchoolYear::class);
    }

    /**
     * Créateur
     */
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Est payée
     */
    public function isPaid(): bool
    {
        return $this->statut === 'payee' || $this->solde <= 0;
    }

    /**
     * Est en retard
     */
    public function isOverdue(): bool
    {
        return $this->date_echeance &&
            $this->date_echeance->isPast() &&
            $this->solde > 0;
    }

    /**
     * Pourcentage payé
     */
    public function getPaymentPercentageAttribute(): float
    {
        if ($this->montant_ttc <= 0) return 0;
        return round(($this->montant_paye / $this->montant_ttc) * 100, 1);
    }

    /**
     * Mettre à jour le statut selon les paiements
     */
    public function updateStatus(): void
    {
        $totalPaid = $this->payments()->where('statut', 'valide')->sum('montant');
        $this->montant_paye = $totalPaid;
        $this->solde = $this->montant_ttc - $totalPaid;

        if ($this->solde <= 0) {
            $this->statut = 'payee';
        } elseif ($totalPaid > 0) {
            $this->statut = 'partiellement_payee';
        }

        $this->save();
    }

    /**
     * Scope: impayées
     */
    public function scopeUnpaid($query)
    {
        return $query->where('solde', '>', 0)->where('statut', '!=', 'annulee');
    }

    /**
     * Scope: en retard
     */
    public function scopeOverdue($query)
    {
        return $query->where('date_echeance', '<', now())
            ->where('solde', '>', 0)
            ->where('statut', '!=', 'annulee');
    }

    /**
     * Scope: par statut
     */
    public function scopeByStatus($query, string $statut)
    {
        return $query->where('statut', $statut);
    }
}
