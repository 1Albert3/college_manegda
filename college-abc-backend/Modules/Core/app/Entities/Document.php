<?php

namespace Modules\Core\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Document extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'description',
        'file_path',
        'type',
        'roles_access',
        'created_by'
    ];

    protected $casts = [
        'roles_access' => 'array',
    ];

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function scopeAccessibleBy($query, User $user)
    {
        if ($user->hasRole('super_admin') || $user->can('manage-documents')) {
            return $query;
        }

        return $query->whereNull('roles_access')
            ->orWhereJsonContains('roles_access', $user->roles->pluck('name'));
    }
}
