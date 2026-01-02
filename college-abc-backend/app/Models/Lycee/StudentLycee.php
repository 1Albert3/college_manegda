<?php

namespace App\Models\Lycee;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\User;

/**
 * Modèle StudentLycee - Base Lycée (school_lycee)
 * 
 * Élèves du lycée (2nde, 1ère, Terminale)
 * Avec gestion des séries (A, C, D, E, F, G) et orientation BAC
 */
class StudentLycee extends Model
{
    use HasFactory, HasUuids, SoftDeletes;

    protected $connection = 'school_lycee';
    protected $table = 'students_lycee';

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
        'serie',
        'options',
        'groupe_sanguin',
        'allergies',
        'vaccinations',
        'orientation_post_bac',
        'previous_school_id',
        'migrated_from_college',
        'is_active',
    ];

    protected $casts = [
        'date_naissance' => 'date',
        'options' => 'array',
        'vaccinations' => 'array',
        'orientation_post_bac' => 'array',
        'migrated_from_college' => 'boolean',
        'is_active' => 'boolean',
    ];

    /**
     * Séries disponibles
     */
    const SERIES = [
        'A' => 'Littéraire',
        'C' => 'Sciences Mathématiques',
        'D' => 'Sciences Expérimentales',
        'E' => 'Sciences et Techniques',
        'F' => 'Techniques Industrielles',
        'G' => 'Techniques de Gestion',
    ];

    /**
     * Générer le matricule automatiquement
     * Format: LYC-[Année]-[Numéro 4 chiffres]
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
                if ($lastStudent && preg_match('/LYC-\d{4}-(\d+)/', $lastStudent->matricule, $matches)) {
                    $nextNumber = intval($matches[1]) + 1;
                }

                $student->matricule = sprintf('LYC-%s-%04d', $year, $nextNumber);
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
        return $this->hasMany(GuardianLycee::class, 'student_id');
    }

    /**
     * Inscriptions
     */
    public function enrollments()
    {
        return $this->hasMany(EnrollmentLycee::class, 'student_id');
    }

    /**
     * Notes
     */
    public function grades()
    {
        return $this->hasMany(GradeLycee::class, 'student_id');
    }

    /**
     * Bulletins
     */
    public function reportCards()
    {
        return $this->hasMany(ReportCardLycee::class, 'student_id');
    }

    /**
     * Absences
     */
    public function attendances()
    {
        return $this->hasMany(AttendanceLycee::class, 'student_id');
    }

    /**
     * Historique scolaire (avec résultats BAC)
     */
    public function history()
    {
        return $this->hasMany(StudentHistoryLycee::class, 'student_id');
    }

    /**
     * Discipline
     */
    public function disciplineRecords()
    {
        return $this->hasMany(DisciplineLycee::class, 'student_id');
    }

    /**
     * Orientation
     */
    public function orientationRecords()
    {
        return $this->hasMany(OrientationLycee::class, 'student_id');
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
     * Libellé de la série
     */
    public function getSerieNameAttribute(): ?string
    {
        return $this->serie ? (self::SERIES[$this->serie] ?? $this->serie) : null;
    }

    /**
     * Est en classe d'examen (Terminale)
     */
    public function isInTerminale(): bool
    {
        return $this->currentClass()?->niveau === 'Tle';
    }

    /**
     * Peut passer le BAC
     */
    public function canTakeBac(): bool
    {
        if (!$this->isInTerminale()) {
            return false;
        }

        // Vérifier qu'il a une série
        if (empty($this->serie)) {
            return false;
        }

        return true;
    }

    /**
     * Résultats BAC (depuis l'historique)
     */
    public function getBacResultsAttribute(): ?array
    {
        $history = $this->history()
            ->where('niveau', 'Tle')
            ->whereNotNull('resultat_bac')
            ->first();

        return $history?->resultat_bac;
    }

    /**
     * A obtenu le BAC
     */
    public function hasBac(): bool
    {
        $results = $this->bac_results;
        return $results && ($results['admis'] ?? false);
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
     * Scope: par série
     */
    public function scopeBySerie($query, string $serie)
    {
        return $query->where('serie', $serie);
    }

    /**
     * Scope: terminales
     */
    public function scopeTerminales($query)
    {
        return $this->scopeByLevel($query, 'Tle');
    }

    /**
     * Scope: migrés depuis Collège
     */
    public function scopeMigratedFromCollege($query)
    {
        return $query->where('migrated_from_college', true);
    }
}
