<?php

namespace Modules\Core\Entities;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Notifications\Notifiable;
use App\Traits\Searchable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, HasRoles, Notifiable, Searchable, HasUuids;

    protected $guard_name = 'sanctum';

    protected $searchable = ['name', 'email', 'phone'];

    protected $table = 'users';

    protected $fillable = [
        'name',
        'email',
        'password',
        'phone',
        'role_type',
        'is_active',
        'last_login_at',
        'profile_type',
        'profile_id'
    ];

    protected $hidden = [
        'password',
        'remember_token'
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'last_login_at' => 'datetime',
        'is_active' => 'boolean',
    ];

    // Relations polymorphiques
    public function profile()
    {
        return $this->morphTo('profile', 'profile_type', 'profile_id');
    }

    protected static function newFactory()
    {
        return \Database\Factories\UserFactory::new();
    }
}
