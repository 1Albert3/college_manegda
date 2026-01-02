<?php

namespace App\Models\Finance;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use App\Models\SchoolYear;

/**
 * Modèle FeeStructure - Grilles Tarifaires
 */
class FeeStructure extends Model
{
    use HasUuids;

    protected $connection = 'school_core';
    protected $table = 'fee_structures';

    protected $fillable = [
        'school_year_id',
        'cycle',
        'niveau',
        'serie',
        'inscription',
        'scolarite',
        'apee',
        'assurance',
        'tenue',
        'fournitures',
        'total',
        'cantine_mensuel',
        'transport_mensuel',
        'reduction_frere_soeur',
        'reduction_paiement_integral',
        'is_active',
    ];

    protected $casts = [
        'inscription' => 'decimal:2',
        'scolarite' => 'decimal:2',
        'apee' => 'decimal:2',
        'assurance' => 'decimal:2',
        'tenue' => 'decimal:2',
        'fournitures' => 'decimal:2',
        'total' => 'decimal:2',
        'cantine_mensuel' => 'decimal:2',
        'transport_mensuel' => 'decimal:2',
        'reduction_frere_soeur' => 'decimal:2',
        'reduction_paiement_integral' => 'decimal:2',
        'is_active' => 'boolean',
    ];

    public function schoolYear()
    {
        return $this->belongsTo(SchoolYear::class);
    }

    /**
     * Calculer le total automatiquement avant la sauvegarde
     */
    protected static function booted()
    {
        static::saving(function ($fee) {
            $fee->total = $fee->inscription +
                $fee->scolarite +
                ($fee->apee ?? 0) +
                ($fee->assurance ?? 0) +
                ($fee->tenue ?? 0) +
                ($fee->fournitures ?? 0);
        });
    }
}
