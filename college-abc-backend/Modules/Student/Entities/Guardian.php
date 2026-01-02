<?php

namespace Modules\Student\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Guardian extends Model
{
    use HasFactory;

    protected $table = 'student_guardians';

    protected $fillable = [
        'student_id',
        'relationship',
        'first_name',
        'last_name',
        'phone',
        'email',
        'profession',
        'address',
        'is_primary',
        'can_pick_up',
    ];

    protected $casts = [
        'is_primary' => 'boolean',
        'can_pick_up' => 'boolean',
    ];

    // Relations
    public function student()
    {
        return $this->belongsTo(Student::class);
    }

    // Scopes
    public function scopePrimary($query)
    {
        return $query->where('is_primary', true);
    }

    public function scopeAuthorizedPickup($query)
    {
        return $query->where('can_pick_up', true);
    }

    // Accessors
    public function getFullNameAttribute()
    {
        return "{$this->first_name} {$this->last_name}";
    }

    public function getRelationshipLabelAttribute()
    {
        $labels = [
            'father' => 'Père',
            'mother' => 'Mère',
            'guardian' => 'Tuteur/Tutrice',
            'uncle' => 'Oncle',
            'aunt' => 'Tante',
            'grandparent' => 'Grand-parent',
            'other' => 'Autre',
        ];

        return $labels[$this->relationship] ?? $this->relationship;
    }
}
