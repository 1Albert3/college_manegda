<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

/**
 * Modèle Role - Base centrale (school_core)
 */
class Role extends Model
{
    use HasUuids;

    // protected $connection = 'school_core';
    protected $table = 'roles';

    protected $fillable = [
        'name',
        'display_name',
        'description',
        'is_system',
    ];

    protected $casts = [
        'is_system' => 'boolean',
    ];

    /**
     * Relation avec les permissions
     */
    public function permissions()
    {
        return $this->belongsToMany(Permission::class, 'role_permissions', 'role_id', 'permission_id')
            ->withTimestamps();
    }

    /**
     * Relation avec les utilisateurs
     */
    public function users()
    {
        return $this->belongsToMany(User::class, 'user_roles', 'role_id', 'user_id')
            ->withTimestamps();
    }

    /**
     * Assigner une permission à ce rôle
     */
    public function givePermissionTo(string $permissionName): void
    {
        $permission = Permission::where('name', $permissionName)->firstOrFail();
        $this->permissions()->syncWithoutDetaching($permission->id);
    }

    /**
     * Retirer une permission de ce rôle
     */
    public function revokePermissionTo(string $permissionName): void
    {
        $permission = Permission::where('name', $permissionName)->first();
        if ($permission) {
            $this->permissions()->detach($permission->id);
        }
    }
}
