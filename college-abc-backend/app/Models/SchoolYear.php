<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

/**
 * Modèle SchoolYear - Base centrale (school_core)
 * 
 * Gestion des années scolaires
 */
class SchoolYear extends Model
{
    use HasUuids;

    protected $connection = 'school_core';
    protected $table = 'school_years';

    protected $fillable = [
        'name',
        'start_date',
        'end_date',
        'is_current',
        'is_locked',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'is_current' => 'boolean',
        'is_locked' => 'boolean',
    ];

    /**
     * Obtenir l'année scolaire en cours
     */
    public static function current(): ?self
    {
        return static::where('is_current', true)->first();
    }

    /**
     * Définir cette année comme l'année courante
     */
    public function setAsCurrent(): void
    {
        // Retirer le statut courant des autres années
        static::where('is_current', true)->update(['is_current' => false]);

        // Définir cette année comme courante
        $this->is_current = true;
        $this->save();
    }

    /**
     * Verrouiller l'année scolaire
     */
    public function lock(): void
    {
        $this->is_locked = true;
        $this->save();
    }

    /**
     * Scope: année courante
     */
    public function scopeCurrent($query)
    {
        return $query->where('is_current', true);
    }

    /**
     * Scope: années non verrouillées
     */
    public function scopeUnlocked($query)
    {
        return $query->where('is_locked', false);
    }
}
