<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Support\Facades\DB;

class User extends Authenticatable
{
    use HasApiTokens, HasUuids;

    protected $connection = 'school_core';
    protected $table = 'users';

    /**
     * Rôles autorisés selon le programme burkinabé
     */
    const ROLES = [
        'super_admin' => 'Super Administrateur',
        'direction' => 'Directeur Général',
        'admin' => 'Administrateur',
        'secretariat' => 'Secrétaire',
        'comptabilite' => 'Comptable',
        'enseignant' => 'Enseignant',
        'parent' => 'Parent',
        'eleve' => 'Élève'
    ];

    protected $fillable = [
        'matricule',
        'email',
        'password',
        'phone',
        'first_name',
        'last_name',
        'role',
        'is_active',
        'email_verified_at',
        'last_login_at',
        'failed_login_attempts',
        'locked_until'
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'last_login_at' => 'datetime',
        'locked_until' => 'datetime',
        'is_active' => 'boolean',
        'failed_login_attempts' => 'integer'
    ];

    /**
     * Nom complet de l'utilisateur
     */
    public function getFullNameAttribute(): string
    {
        return trim(($this->first_name ?? '') . ' ' . ($this->last_name ?? '')) ?: $this->email;
    }

    /**
     * Validation stricte des rôles
     */
    public function hasRole(string $role): bool
    {
        return $this->role === $role;
    }

    public function hasAnyRole(array $roles): bool
    {
        return in_array($this->role, $roles);
    }

    /**
     * Permissions par rôle (Programme Burkinabé)
     */
    public function canManageGrades(): bool
    {
        return $this->hasAnyRole(['enseignant', 'admin', 'direction', 'super_admin']);
    }

    public function canManageFinances(): bool
    {
        return $this->hasAnyRole(['comptabilite', 'admin', 'direction', 'super_admin']);
    }

    public function canManageEnrollments(): bool
    {
        return $this->hasAnyRole(['secretariat', 'admin', 'direction', 'super_admin']);
    }

    public function canViewReports(): bool
    {
        return $this->hasAnyRole(['direction', 'admin', 'enseignant', 'parent', 'super_admin']);
    }

    /**
     * Audit automatique des connexions
     */
    public function recordLogin(): void
    {
        $this->update([
            'last_login_at' => now(),
            'failed_login_attempts' => 0,
            'locked_until' => null
        ]);

        // Log dans audit_logs
        DB::connection('school_core')->table('audit_logs')->insert([
            'id' => \Illuminate\Support\Str::uuid()->toString(),
            'user_id' => $this->id,
            'action' => 'login',
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'created_at' => now()
        ]);
    }

    public function recordFailedLogin(): void
    {
        $attempts = $this->failed_login_attempts + 1;
        $lockedUntil = null;

        // Verrouillage après 5 tentatives (sécurité renforcée)
        if ($attempts >= 5) {
            $lockedUntil = now()->addMinutes(30);
        }

        $this->update([
            'failed_login_attempts' => $attempts,
            'locked_until' => $lockedUntil
        ]);
    }

    public function isLocked(): bool
    {
        return $this->locked_until && $this->locked_until->isFuture();
    }
}
