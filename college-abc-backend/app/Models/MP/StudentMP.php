<?php

namespace App\Models\MP;

use App\Models\User;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Modèle StudentMP - Élèves Maternelle/Primaire
 * Base de données: school_maternelle_primaire
 * 
 * Conforme au formulaire d'inscription du cahier des charges
 */
class StudentMP extends Model
{
    use HasUuids, SoftDeletes;

    protected $connection = 'school_mp';
    protected $table = 'students_mp';

    protected $fillable = [
        'user_id',
        'matricule',
        // Informations élève
        'nom',
        'prenoms',
        'date_naissance',
        'lieu_naissance',
        'sexe',
        'nationalite',
        'photo_identite',
        'extrait_naissance',
        // Statut
        'statut_inscription',
        'etablissement_origine',
        // Médical
        'groupe_sanguin',
        'allergies',
        'vaccinations',
        'is_active',
    ];

    protected $casts = [
        'date_naissance' => 'date',
        'vaccinations' => 'array',
        'is_active' => 'boolean',
    ];

    /**
     * Boot du modèle - Génération automatique du matricule
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($student) {
            if (empty($student->matricule)) {
                $student->matricule = static::generateMatricule();
            }
        });
    }

    /**
     * Génère un matricule unique: MP-2025-0001
     */
    public static function generateMatricule(): string
    {
        $year = date('Y');
        $prefix = "MP-{$year}-";

        $lastStudent = static::where('matricule', 'like', "{$prefix}%")
            ->orderBy('matricule', 'desc')
            ->first();

        if ($lastStudent) {
            $lastNumber = intval(substr($lastStudent->matricule, -4));
            $newNumber = str_pad($lastNumber + 1, 4, '0', STR_PAD_LEFT);
        } else {
            $newNumber = '0001';
        }

        return $prefix . $newNumber;
    }

    /**
     * Nom complet de l'élève
     */
    public function getFullNameAttribute(): string
    {
        return "{$this->nom} {$this->prenoms}";
    }

    /**
     * Âge de l'élève
     */
    public function getAgeAttribute(): int
    {
        return $this->date_naissance->age;
    }

    /**
     * Relation avec le compte utilisateur (core)
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Relation avec les tuteurs
     */
    public function guardians()
    {
        return $this->hasMany(GuardianMP::class, 'student_id');
    }

    /**
     * Obtenir le père
     */
    public function father()
    {
        return $this->guardians()->where('type', 'pere')->first();
    }

    /**
     * Obtenir la mère
     */
    public function mother()
    {
        return $this->guardians()->where('type', 'mere')->first();
    }

    /**
     * Obtenir le tuteur légal
     */
    public function legalGuardian()
    {
        return $this->guardians()->where('type', 'tuteur')->first();
    }

    /**
     * Contact d'urgence
     */
    public function emergencyContact()
    {
        return $this->guardians()->where('est_contact_urgence', true)->first();
    }

    /**
     * Relation avec les inscriptions
     */
    public function enrollments()
    {
        return $this->hasMany(EnrollmentMP::class, 'student_id');
    }

    /**
     * Inscription de l'année courante
     */
    /**
     * Inscription de l'année courante
     */
    public function currentEnrollment()
    {
        $currentYear = \App\Models\SchoolYear::current();

        if (!$currentYear) {
            return null;
        }

        return $this->enrollments()
            ->where('school_year_id', $currentYear->id)
            ->first();
    }

    /**
     * Relation avec les notes
     */
    public function grades()
    {
        return $this->hasMany(GradeMP::class, 'student_id');
    }

    /**
     * Relation avec les compétences (maternelle)
     */
    public function competences()
    {
        return $this->hasMany(CompetenceMP::class, 'student_id');
    }

    /**
     * Relation avec les absences
     */
    public function attendances()
    {
        return $this->hasMany(AttendanceMP::class, 'student_id');
    }

    /**
     * Relation avec les bulletins
     */
    public function reportCards()
    {
        return $this->hasMany(ReportCardMP::class, 'student_id');
    }

    /**
     * Relation avec l'historique
     */
    public function history()
    {
        return $this->hasMany(StudentHistoryMP::class, 'student_id');
    }

    /**
     * Scope: élèves actifs
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope: par statut inscription
     */
    public function scopeByStatut($query, string $statut)
    {
        return $query->where('statut_inscription', $statut);
    }

    /**
     * Scope: nouveaux élèves
     */
    public function scopeNouveaux($query)
    {
        return $query->where('statut_inscription', 'nouveau');
    }

    /**
     * Scope: transferts
     */
    public function scopeTransferts($query)
    {
        return $query->where('statut_inscription', 'transfert');
    }
}
