<?php

namespace App\Models\Finance;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use App\Models\User;

/**
 * Modèle Payment - Paiements
 * 
 * Enregistrement des paiements reçus
 */
class Payment extends Model
{
    use HasUuids;

    // protected $connection = 'school_core';
    protected $table = 'payments';

    protected $fillable = [
        'reference',
        'invoice_id',
        'student_id',
        'student_database',
        'montant',
        'mode_paiement',
        'date_paiement',
        'reference_transaction',
        'banque',
        'numero_cheque',
        'statut',
        'notes',
        'received_by',
        'validated_by',
        'validated_at',
    ];

    protected $casts = [
        'montant' => 'decimal:2',
        'date_paiement' => 'date',
        'validated_at' => 'datetime',
    ];

    /**
     * Modes de paiement
     */
    const MODES = [
        'especes' => 'Espèces',
        'cheque' => 'Chèque',
        'virement' => 'Virement bancaire',
        'mobile_money' => 'Mobile Money',
        'carte' => 'Carte bancaire',
    ];

    /**
     * Statuts
     */
    const STATUTS = [
        'en_attente' => 'En attente',
        'valide' => 'Validé',
        'rejete' => 'Rejeté',
        'annule' => 'Annulé',
    ];

    /**
     * Générer la référence
     */
    protected static function booted()
    {
        static::creating(function ($payment) {
            if (empty($payment->reference)) {
                $date = date('Ymd');
                $lastPayment = static::whereDate('created_at', today())
                    ->orderByDesc('created_at')
                    ->first();

                $nextNumber = 1;
                if ($lastPayment && preg_match('/PAY-\d{8}-(\d+)/', $lastPayment->reference, $matches)) {
                    $nextNumber = intval($matches[1]) + 1;
                }

                $payment->reference = sprintf('PAY-%s-%04d', $date, $nextNumber);
            }
        });

        static::created(function ($payment) {
            // Mettre à jour le statut de la facture
            if ($payment->invoice_id && $payment->statut === 'valide') {
                $payment->invoice?->updateStatus();
            }
        });

        static::updated(function ($payment) {
            if ($payment->invoice_id) {
                $payment->invoice?->updateStatus();
            }
        });
    }

    /**
     * Facture associée
     */
    public function invoice()
    {
        return $this->belongsTo(Invoice::class);
    }

    /**
     * Agent qui a reçu le paiement
     */
    public function receiver()
    {
        return $this->belongsTo(User::class, 'received_by');
    }

    /**
     * Agent qui a validé
     */
    public function validator()
    {
        return $this->belongsTo(User::class, 'validated_by');
    }

    /**
     * Valider le paiement
     */
    public function validate(string $userId): void
    {
        $this->update([
            'statut' => 'valide',
            'validated_by' => $userId,
            'validated_at' => now(),
        ]);
    }

    /**
     * Rejeter le paiement
     */
    public function reject(string $userId, string $reason = null): void
    {
        $this->update([
            'statut' => 'rejete',
            'validated_by' => $userId,
            'validated_at' => now(),
            'notes' => $reason ? "{$this->notes}\nRejet: {$reason}" : $this->notes,
        ]);
    }

    /**
     * Est validé
     */
    public function isValidated(): bool
    {
        return $this->statut === 'valide';
    }

    /**
     * Libellé du mode de paiement
     */
    public function getModeNameAttribute(): string
    {
        return self::MODES[$this->mode_paiement] ?? $this->mode_paiement;
    }

    /**
     * Scope: validés uniquement
     */
    public function scopeValidated($query)
    {
        return $query->where('statut', 'valide');
    }

    /**
     * Scope: par mode de paiement
     */
    public function scopeByMode($query, string $mode)
    {
        return $query->where('mode_paiement', $mode);
    }

    /**
     * Scope: période
     */
    public function scopeBetweenDates($query, $startDate, $endDate)
    {
        return $query->whereBetween('date_paiement', [$startDate, $endDate]);
    }
}
