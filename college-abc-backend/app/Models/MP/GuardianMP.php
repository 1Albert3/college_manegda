<?php

namespace App\Models\MP;

use App\Models\User;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

/**
 * Modèle GuardianMP - Tuteurs/Parents Maternelle/Primaire
 * Base de données: school_maternelle_primaire
 */
class GuardianMP extends Model
{
    use HasUuids;

    protected $connection = 'school_mp';
    protected $table = 'guardians_mp';

    protected $fillable = [
        'student_id',
        'type',
        'nom_complet',
        'profession',
        'telephone_1',
        'telephone_2',
        'email',
        'adresse_physique',
        'est_contact_urgence',
        'lien_parente',
        'user_id',
    ];

    protected $casts = [
        'est_contact_urgence' => 'boolean',
    ];

    /**
     * Types de tuteurs
     */
    const TYPE_PERE = 'pere';
    const TYPE_MERE = 'mere';
    const TYPE_TUTEUR = 'tuteur';

    /**
     * Relation avec l'élève
     */
    public function student()
    {
        return $this->belongsTo(StudentMP::class, 'student_id');
    }

    /**
     * Relation avec le compte utilisateur parent
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Vérifier si c'est le père
     */
    public function isPere(): bool
    {
        return $this->type === self::TYPE_PERE;
    }

    /**
     * Vérifier si c'est la mère
     */
    public function isMere(): bool
    {
        return $this->type === self::TYPE_MERE;
    }

    /**
     * Vérifier si c'est un tuteur
     */
    public function isTuteur(): bool
    {
        return $this->type === self::TYPE_TUTEUR;
    }

    /**
     * Obtenir le téléphone principal
     */
    public function getPrimaryPhoneAttribute(): string
    {
        return $this->telephone_1;
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
