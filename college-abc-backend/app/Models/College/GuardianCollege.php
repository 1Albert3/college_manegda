<?php

namespace App\Models\College;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

/**
 * Modèle GuardianCollege - Base Collège (school_college)
 * 
 * Parents/Tuteurs des élèves du collège
 */
class GuardianCollege extends Model
{
    use HasUuids;

    protected $connection = 'school_college';
    protected $table = 'guardians_college';

    protected $fillable = [
        'student_id',
        'user_id',
        'type',
        'nom_complet',
        'profession',
        'telephone_1',
        'telephone_2',
        'email',
        'adresse_physique',
        'est_contact_urgence',
        'lien_parente',
    ];

    protected $casts = [
        'est_contact_urgence' => 'boolean',
    ];

    /**
     * Types de tuteurs disponibles
     */
    const TYPES = ['pere', 'mere', 'tuteur'];

    /**
     * Élève associé
     */
    public function student()
    {
        return $this->belongsTo(StudentCollege::class, 'student_id');
    }

    /**
     * Téléphone principal
     */
    public function getPrimaryPhoneAttribute(): ?string
    {
        return $this->telephone_1 ?: $this->telephone_2;
    }

    /**
     * Scope: contacts d'urgence
     */
    public function scopeEmergencyContacts($query)
    {
        return $query->where('est_contact_urgence', true);
    }

    /**
     * Scope: par type
     */
    public function scopeByType($query, string $type)
    {
        return $query->where('type', $type);
    }
}
