<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

/**
 * Modèle Permission - Base centrale (school_core)
 */
class Permission extends Model
{
    use HasUuids;

    // protected $connection = 'school_core';
    protected $table = 'permissions';

    protected $fillable = [
        'name',
        'display_name',
        'module',
        'description',
    ];

    /**
     * Relation avec les rôles
     */
    public function roles()
    {
        return $this->belongsToMany(Role::class, 'role_permissions', 'permission_id', 'role_id')
            ->withTimestamps();
    }
}
