<?php

namespace App\Models\Finance;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use App\Models\SchoolYear;
use App\Models\User;

/**
 * Modèle Scholarship - Bourses et Réductions
 */
class Scholarship extends Model
{
    use HasUuids;

    protected $connection = 'school_core';
    protected $table = 'scholarships';

    protected $fillable = [
        'student_id',
        'student_database',
        'school_year_id',
        'type',
        'motif',
        'mode',
        'valeur',
        'montant_accorde',
        'approved_by',
        'approved_at',
        'statut',
    ];

    protected $casts = [
        'valeur' => 'decimal:2',
        'montant_accorde' => 'decimal:2',
        'approved_at' => 'datetime',
    ];

    const TYPES = [
        'bourse_merite' => 'Bourse d\'Excellence',
        'bourse_sociale' => 'Bourse Sociale',
        'reduction_fratrie' => 'Réduction Fratrie',
        'autre' => 'Autre',
    ];

    public function schoolYear()
    {
        return $this->belongsTo(SchoolYear::class);
    }

    public function approvedBy()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    /**
     * Obtenir le libellé du type
     */
    public function getTypeNameAttribute(): string
    {
        return self::TYPES[$this->type] ?? $this->type;
    }
}
