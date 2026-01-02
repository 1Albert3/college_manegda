<?php

namespace App\Models\College;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\User;

/**
 * Modèle StudentCollege - Base Collège (school_college)
 * 
 * Élèves du collège (6ème, 5ème, 4ème, 3ème)
 * Avec gestion des LV2, options et migration vers lycée
 */
class StudentCollege extends Model
{
    use HasFactory, HasUuids, SoftDeletes;

    protected $connection = 'school_college';
    protected $table = 'students_college';

    protected $fillable = [
        'user_id',
        'matricule',
        'nom',
        'prenoms',
        'date_naissance',
        'lieu_naissance',
        'sexe',
        'nationalite',
        'photo_identite',
        'extrait_naissance',
        'statut_inscription',
        'etablissement_origine',
        'lv2',
        'options',
        'groupe_sanguin',
        'allergies',
        'vaccinations',
        'previous_school_id',
        'migrated_from_mp',
        'is_active',
    ];

    protected $casts = [
        'date_naissance' => 'date',
        'options' => 'array',
        'vaccinations' => 'array',
        'migrated_from_mp' => 'boolean',
        'is_active' => 'boolean',
    ];

    /**
     * Générer le matricule automatiquement
     * Format: COL-[Année]-[Numéro 4 chiffres]
     */
    protected static function booted()
    {
        static::creating(function ($student) {
            if (empty($student->matricule)) {
                $year = date('Y');
                $lastStudent = static::whereYear('created_at', $year)
                    ->orderByDesc('created_at')
                    ->first();

                $nextNumber = 1;
                if ($lastStudent && preg_match('/COL-\d{4}-(\d+)/', $lastStudent->matricule, $matches)) {
                    $nextNumber = intval($matches[1]) + 1;
                }

                $student->matricule = sprintf('COL-%s-%04d', $year, $nextNumber);
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
     * Tuteurs de l'élève
     */
    public function guardians()
    {
        return $this->hasMany(GuardianCollege::class, 'student_id');
    }

    /**
     * Inscriptions
     */
    public function enrollments()
    {
        return $this->hasMany(EnrollmentCollege::class, 'student_id');
    }

    /**
     * Notes
     */
    public function grades()
    {
        return $this->hasMany(GradeCollege::class, 'student_id');
    }

    /**
     * Bulletins
     */
    public function reportCards()
    {
        return $this->hasMany(ReportCardCollege::class, 'student_id');
    }

    /**
     * Absences
     */
    public function attendances()
    {
        return $this->hasMany(AttendanceCollege::class, 'student_id');
    }

    /**
     * Historique scolaire
     */
    public function history()
    {
        return $this->hasMany(StudentHistoryCollege::class, 'student_id');
    }

    /**
     * Discipline
     */
    public function disciplineRecords()
    {
        return $this->hasMany(DisciplineCollege::class, 'student_id');
    }

    /**
     * Inscription courante
     */
    /**
     * Inscription courante
     */
    public function currentEnrollment()
    {
        $currentYear = \App\Models\SchoolYear::current();

        if (!$currentYear) {
            return null;
        }

        return $this->enrollments()
            ->where('school_year_id', $currentYear->id)
            ->where('statut', 'validee')
            ->first();
    }

    /**
     * Classe actuelle
     */
    public function currentClass()
    {
        return $this->currentEnrollment()?->class;
    }

    /**
     * Niveau actuel
     */
    public function getCurrentNiveauAttribute()
    {
        return $this->currentClass()?->niveau ?? 'N/A';
    }

    /**
     * Nom complet
     */
    public function getFullNameAttribute(): string
    {
        return "{$this->nom} {$this->prenoms}";
    }

    /**
     * Âge
     */
    public function getAgeAttribute(): int
    {
        return $this->date_naissance->age;
    }

    /**
     * Photo URL
     */
    public function getPhotoUrlAttribute(): ?string
    {
        if (!$this->photo_identite) {
            return null;
        }
        return asset('storage/' . $this->photo_identite);
    }

    /**
     * Père
     */
    public function getPereAttribute()
    {
        return $this->guardians->where('type', 'pere')->first();
    }

    /**
     * Mère
     */
    public function getMereAttribute()
    {
        return $this->guardians->where('type', 'mere')->first();
    }

    /**
     * Contact urgence
     */
    public function getUrgencyContactAttribute()
    {
        return $this->guardians->where('est_contact_urgence', true)->first();
    }

    /**
     * Éligible pour migration vers lycée (si en 3ème et passage)
     */
    public function canMigrateToLycee(): bool
    {
        $currentLevel = $this->currentClass()?->niveau;

        if ($currentLevel !== '3eme') {
            return false;
        }

        // Vérifier la décision de passage
        $history = $this->history()
            ->where('school_year_id', \App\Models\SchoolYear::current()?->id)
            ->first();

        return $history && $history->decision === 'passage';
    }

    /**
     * Scope: actifs uniquement
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope: par niveau
     */
    public function scopeByLevel($query, string $niveau)
    {
        return $query->whereHas('enrollments.class', function ($q) use ($niveau) {
            $q->where('niveau', $niveau);
        });
    }

    /**
     * Scope: par classe
     */
    public function scopeInClass($query, string $classId)
    {
        return $query->whereHas('enrollments', function ($q) use ($classId) {
            $q->where('class_id', $classId);
        });
    }

    /**
     * Scope: migrés depuis MP
     */
    public function scopeMigratedFromMP($query)
    {
        return $query->where('migrated_from_mp', true);
    }
}
