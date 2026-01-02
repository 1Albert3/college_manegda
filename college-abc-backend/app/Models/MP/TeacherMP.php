<?php

namespace App\Models\MP;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use App\Models\User;

/**
 * Modèle TeacherMP - Base MP (school_maternelle_primaire)
 * 
 * Enseignants du Maternelle/Primaire
 */
class TeacherMP extends Model
{
    use HasUuids;

    protected $connection = 'school_mp';
    protected $table = 'teachers_mp';

    protected $fillable = [
        'user_id',
        'matricule',
        'nom',
        'prenoms',
        'date_naissance',
        'lieu_naissance',
        'sexe',
        'nationalite',
        'telephone',
        'email',
        'adresse',
        'photo',
        'diplome_principal',
        'specialite',
        'date_embauche',
        'type_contrat',
        'statut',
        'is_active',
    ];

    protected $casts = [
        'date_naissance' => 'date',
        'date_embauche' => 'date',
        'is_active' => 'boolean',
    ];

    /**
     * Types de contrat
     */
    const CONTRATS = [
        'cdi' => 'CDI',
        'cdd' => 'CDD',
        'vacation' => 'Vacataire',
        'stage' => 'Stagiaire',
    ];

    /**
     * Statuts
     */
    const STATUTS = [
        'actif' => 'Actif',
        'conge' => 'En congé',
        'suspendu' => 'Suspendu',
        'demission' => 'Démissionnaire',
    ];

    /**
     * Générer le matricule automatiquement
     */
    protected static function booted()
    {
        static::creating(function ($teacher) {
            if (empty($teacher->matricule)) {
                $year = date('Y');
                $lastTeacher = static::whereYear('created_at', $year)
                    ->orderByDesc('created_at')
                    ->first();

                $nextNumber = 1;
                if ($lastTeacher && preg_match('/MPENS-\d{4}-(\d+)/', $lastTeacher->matricule, $matches)) {
                    $nextNumber = intval($matches[1]) + 1;
                }

                $teacher->matricule = sprintf('MPENS-%s-%03d', $year, $nextNumber);
            }
        });
    }

    /**
     * Relation avec l'utilisateur (compte)
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Classes où il enseigne
     */
    public function classesEnseigne()
    {
        return $this->hasMany(ClassMP::class, 'teacher_id');
    }

    /**
     * Nom complet
     */
    public function getFullNameAttribute(): string
    {
        return "{$this->nom} {$this->prenoms}";
    }

    /**
     * Initiales
     */
    public function getInitialsAttribute(): string
    {
        return strtoupper(substr($this->nom, 0, 1) . substr($this->prenoms, 0, 1));
    }

    /**
     * Photo URL
     */
    public function getPhotoUrlAttribute(): ?string
    {
        if (!$this->photo) {
            return null;
        }
        return asset('storage/' . $this->photo);
    }

    /**
     * Ancienneté en années
     */
    public function getSeniorityAttribute(): int
    {
        if (!$this->date_embauche) {
            return 0;
        }
        return $this->date_embauche->diffInYears(now());
    }

    /**
     * Est titulaire (CDI)
     */
    public function isTitulaire(): bool
    {
        return $this->type_contrat === 'cdi';
    }

    /**
     * Scope: actifs uniquement
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true)->where('statut', 'actif');
    }

    /**
     * Scope: par spécialité
     */
    public function scopeBySpecialite($query, string $specialite)
    {
        return $query->where('specialite', $specialite);
    }
}
