<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

/**
 * Modèle AuditLog - Base centrale (school_core)
 * 
 * Traçabilité complète des actions
 */
class AuditLog extends Model
{
    use HasUuids;

    // protected $connection = 'school_core';
    protected $table = 'audit_logs';

    public $timestamps = false;

    protected $fillable = [
        'user_id',
        'action',
        'model_type',
        'model_id',
        'old_values',
        'new_values',
        'ip_address',
        'user_agent',
        'url',
        'method',
        'created_at',
    ];

    protected $casts = [
        'old_values' => 'array',
        'new_values' => 'array',
        'created_at' => 'datetime',
    ];

    /**
     * Relation avec l'utilisateur
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Enregistrer une action d'audit
     */
    public static function log(
        string $action,
        ?string $modelType = null,
        ?string $modelId = null,
        ?array $oldValues = null,
        ?array $newValues = null,
        ?string $userId = null
    ): self {
        return static::create([
            'user_id' => $userId ?? Auth::id(),
            'action' => $action,
            'model_type' => $modelType,
            'model_id' => $modelId,
            'old_values' => $oldValues,
            'new_values' => $newValues,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'url' => request()->fullUrl(),
            'method' => request()->method(),
            'created_at' => now(),
        ]);
    }

    /**
     * Scope: par utilisateur
     */
    public function scopeByUser($query, string $userId)
    {
        return $query->where('user_id', $userId);
    }

    /**
     * Scope: par action
     */
    public function scopeByAction($query, string $action)
    {
        return $query->where('action', $action);
    }

    /**
     * Scope: par modèle
     */
    public function scopeByModel($query, string $modelType)
    {
        return $query->where('model_type', $modelType);
    }

    /**
     * Scope: période
     */
    public function scopeBetweenDates($query, $startDate, $endDate)
    {
        return $query->whereBetween('created_at', [$startDate, $endDate]);
    }
}
