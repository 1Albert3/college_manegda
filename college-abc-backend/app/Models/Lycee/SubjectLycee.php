<?php

namespace App\Models\Lycee;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

/**
 * Modèle SubjectLycee - Base Lycée (school_lycee)
 * 
 * Matières avec coefficients différenciés par niveau ET par série
 * Conforme au système burkinabè du BAC
 */
class SubjectLycee extends Model
{
    use HasUuids;

    protected $connection = 'school_lycee';
    protected $table = 'subjects_lycee';

    protected $fillable = [
        'code',
        'nom',
        'description',
        'coefficient_2nde',
        'coefficient_1ere_A',
        'coefficient_1ere_C',
        'coefficient_1ere_D',
        'coefficient_1ere_E',
        'coefficient_1ere_F',
        'coefficient_1ere_G',
        'coefficient_tle_A',
        'coefficient_tle_C',
        'coefficient_tle_D',
        'coefficient_tle_E',
        'coefficient_tle_F',
        'coefficient_tle_G',
        'series_applicables',
        'is_active',
    ];

    protected $casts = [
        'coefficient_2nde' => 'integer',
        'coefficient_1ere_A' => 'integer',
        'coefficient_1ere_C' => 'integer',
        'coefficient_1ere_D' => 'integer',
        'coefficient_1ere_E' => 'integer',
        'coefficient_1ere_F' => 'integer',
        'coefficient_1ere_G' => 'integer',
        'coefficient_tle_A' => 'integer',
        'coefficient_tle_C' => 'integer',
        'coefficient_tle_D' => 'integer',
        'coefficient_tle_E' => 'integer',
        'coefficient_tle_F' => 'integer',
        'coefficient_tle_G' => 'integer',
        'series_applicables' => 'array',
        'is_active' => 'boolean',
    ];

    /**
     * Matières standard du lycée burkinabè avec coefficients par série
     * Format: [code, nom, coeff_2nde, A, C, D, E, F, G (1ère), A, C, D, E, F, G (Tle)]
     */
    const MATIERES_STANDARD = [
        // Matières communes
        ['code' => 'FRA', 'nom' => 'Français', '2nde' => 3, '1ere' => [5, 3, 3, 3, 3, 3], 'tle' => [5, 2, 2, 2, 2, 2]],
        ['code' => 'PHI', 'nom' => 'Philosophie', '2nde' => 2, '1ere' => [4, 2, 2, 2, 2, 2], 'tle' => [6, 3, 3, 3, 3, 3]],
        ['code' => 'HG', 'nom' => 'Histoire-Géographie', '2nde' => 3, '1ere' => [4, 2, 2, 2, 2, 2], 'tle' => [4, 2, 2, 2, 2, 2]],
        ['code' => 'EPS', 'nom' => 'Éducation Physique', '2nde' => 2, '1ere' => [1, 1, 1, 1, 1, 1], 'tle' => [1, 1, 1, 1, 1, 1]],
        ['code' => 'ANG', 'nom' => 'Anglais', '2nde' => 2, '1ere' => [3, 2, 2, 2, 2, 2], 'tle' => [3, 2, 2, 2, 2, 2]],

        // Matières scientifiques
        ['code' => 'MAT', 'nom' => 'Mathématiques', '2nde' => 4, '1ere' => [2, 6, 5, 5, 4, 3], 'tle' => [2, 7, 5, 5, 4, 3]],
        ['code' => 'PC', 'nom' => 'Physique-Chimie', '2nde' => 3, '1ere' => [2, 5, 4, 4, 4, 2], 'tle' => [1, 6, 4, 4, 4, 2]],
        ['code' => 'SVT', 'nom' => 'Sciences de la Vie et de la Terre', '2nde' => 3, '1ere' => [2, 3, 5, 3, 0, 2], 'tle' => [1, 2, 6, 3, 0, 2]],

        // Matières série A
        ['code' => 'LV2', 'nom' => 'Langue Vivante 2', '2nde' => 2, '1ere' => [4, 2, 2, 2, 2, 2], 'tle' => [4, 2, 2, 2, 2, 2]],
        ['code' => 'LAT', 'nom' => 'Latin', '2nde' => 0, '1ere' => [3, 0, 0, 0, 0, 0], 'tle' => [3, 0, 0, 0, 0, 0]],

        // Matières techniques
        ['code' => 'ECO', 'nom' => 'Économie', '2nde' => 0, '1ere' => [0, 0, 0, 0, 0, 5], 'tle' => [0, 0, 0, 0, 0, 6]],
        ['code' => 'CPT', 'nom' => 'Comptabilité', '2nde' => 0, '1ere' => [0, 0, 0, 0, 0, 4], 'tle' => [0, 0, 0, 0, 0, 5]],
        ['code' => 'TCH', 'nom' => 'Techniques Industrielles', '2nde' => 0, '1ere' => [0, 0, 0, 0, 5, 0], 'tle' => [0, 0, 0, 0, 6, 0]],
    ];

    /**
     * Affectations (enseignants assignés)
     */
    public function assignments()
    {
        return $this->hasMany(AssignmentLycee::class, 'subject_id');
    }

    /**
     * Notes
     */
    public function grades()
    {
        return $this->hasMany(GradeLycee::class, 'subject_id');
    }

    /**
     * Obtenir le coefficient pour un niveau et une série donnés
     */
    public function getCoefficientFor(string $niveau, ?string $serie = null): int
    {
        if ($niveau === '2nde') {
            return $this->coefficient_2nde ?? 0;
        }

        if (!$serie) {
            return 0;
        }

        $niveauKey = $niveau === '1ere' ? '1ere' : 'tle';
        $column = "coefficient_{$niveauKey}_{$serie}";

        return $this->{$column} ?? 0;
    }

    /**
     * Vérifie si la matière est applicable à une série
     */
    public function isApplicableTo(string $serie): bool
    {
        if (empty($this->series_applicables)) {
            return true; // Toutes les séries par défaut
        }

        return in_array($serie, $this->series_applicables);
    }

    /**
     * Scope: actives uniquement
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope: par série (avec coeff > 0)
     */
    public function scopeForSerie($query, string $serie, string $niveau = 'tle')
    {
        $niveauKey = $niveau === '1ere' ? '1ere' : 'tle';
        $column = "coefficient_{$niveauKey}_{$serie}";

        return $query->where($column, '>', 0);
    }

    /**
     * Scope: pour la seconde
     */
    public function scopeForSeconde($query)
    {
        return $query->where('coefficient_2nde', '>', 0);
    }
}
