<?php

namespace App\Models\College;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

/**
 * Modèle SubjectCollege - Base Collège (school_college)
 * 
 * Matières avec coefficients par niveau (6ème, 5ème, 4ème, 3ème)
 * Conforme au programme burkinabè
 */
class SubjectCollege extends Model
{
    use HasUuids;

    protected $connection = 'school_college';
    protected $table = 'subjects_college';

    protected $fillable = [
        'code',
        'nom',
        'description',
        'coefficient_6eme',
        'coefficient_5eme',
        'coefficient_4eme',
        'coefficient_3eme',
        'is_active',
    ];

    protected $casts = [
        'coefficient_6eme' => 'integer',
        'coefficient_5eme' => 'integer',
        'coefficient_4eme' => 'integer',
        'coefficient_3eme' => 'integer',
        'is_active' => 'boolean',
    ];

    /**
     * Matières standard du collège burkinabè
     */
    const MATIERES_STANDARD = [
        ['code' => 'FRA', 'nom' => 'Français', 'coeffs' => [4, 4, 4, 4]],
        ['code' => 'MAT', 'nom' => 'Mathématiques', 'coeffs' => [4, 4, 4, 4]],
        ['code' => 'ANG', 'nom' => 'Anglais (LV1)', 'coeffs' => [2, 2, 2, 2]],
        ['code' => 'HG', 'nom' => 'Histoire-Géographie', 'coeffs' => [2, 2, 2, 2]],
        ['code' => 'SVT', 'nom' => 'Sciences de la Vie et de la Terre', 'coeffs' => [2, 2, 2, 2]],
        ['code' => 'PC', 'nom' => 'Physique-Chimie', 'coeffs' => [2, 2, 2, 2]],
        ['code' => 'EPS', 'nom' => 'Éducation Physique et Sportive', 'coeffs' => [1, 1, 1, 1]],
        ['code' => 'ECM', 'nom' => 'Éducation Civique et Morale', 'coeffs' => [1, 1, 1, 1]],
        ['code' => 'LV2', 'nom' => 'Langue Vivante 2 (Allemand/Espagnol)', 'coeffs' => [1, 2, 2, 2]],
        ['code' => 'ART', 'nom' => 'Arts Plastiques', 'coeffs' => [1, 1, 1, 1]],
        ['code' => 'MUS', 'nom' => 'Éducation Musicale', 'coeffs' => [1, 1, 1, 1]],
    ];

    /**
     * Affectations (enseignants assignés)
     */
    public function assignments()
    {
        return $this->hasMany(AssignmentCollege::class, 'subject_id');
    }

    /**
     * Notes
     */
    public function grades()
    {
        return $this->hasMany(GradeCollege::class, 'subject_id');
    }

    /**
     * Obtenir le coefficient pour un niveau donné
     */
    public function getCoefficientForLevel(string $niveau): int
    {
        $map = [
            '6eme' => $this->coefficient_6eme,
            '5eme' => $this->coefficient_5eme,
            '4eme' => $this->coefficient_4eme,
            '3eme' => $this->coefficient_3eme,
        ];

        return $map[$niveau] ?? 1;
    }

    /**
     * Scope: actives uniquement
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope: par niveau (avec coeff > 0)
     */
    public function scopeForLevel($query, string $niveau)
    {
        $column = "coefficient_{$niveau}";
        return $query->where($column, '>', 0);
    }
}
