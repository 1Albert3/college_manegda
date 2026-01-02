<?php

namespace App\Models\MP;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Schema;

/**
 * Modèle SubjectMP - Base MP (school_maternelle_primaire)
 * 
 * Matières/Domaines du Maternelle et Primaire
 * Adapté au système éducatif burkinabè
 */
class SubjectMP extends Model
{
    use HasUuids;

    protected $connection = 'school_mp';
    protected $table = 'subjects_mp';

    protected $fillable = [
        'code',
        'nom',
        'description',
        'categorie',
        'coefficient_maternelle',
        'coefficient_cp_ce1',
        'coefficient_ce2',
        'coefficient_cm1_cm2',
        'type_evaluation',
        'is_active',
    ];

    protected $casts = [
        'coefficient_maternelle' => 'integer',
        'coefficient_cp_ce1' => 'integer',
        'coefficient_ce2' => 'integer',
        'coefficient_cm1_cm2' => 'integer',
        'is_active' => 'boolean',
    ];

    /**
     * Catégories de matières
     */
    const CATEGORIES = [
        'communication' => 'Communication et expression',
        'sciences' => 'Mathématiques et sciences',
        'eveil' => 'Éveil',
        'eps' => 'Éducation physique',
        'arts' => 'Arts et culture',
    ];

    /**
     * Types d'évaluation
     */
    const EVALUATION_TYPES = [
        'note' => 'Note chiffrée',
        'competence' => 'Compétences (acquis/non acquis)',
        'mixte' => 'Mixte',
    ];

    /**
     * Matières standard du primaire burkinabè
     */
    const MATIERES_STANDARD = [
        ['code' => 'FRA', 'nom' => 'Français', 'cat' => 'communication', 'coeffs' => [2, 4, 4, 4]],
        ['code' => 'MAT', 'nom' => 'Mathématiques', 'cat' => 'sciences', 'coeffs' => [2, 4, 4, 4]],
        ['code' => 'ECR', 'nom' => 'Écriture', 'cat' => 'communication', 'coeffs' => [1, 2, 1, 1]],
        ['code' => 'LEC', 'nom' => 'Lecture', 'cat' => 'communication', 'coeffs' => [2, 3, 2, 2]],
        ['code' => 'SVT', 'nom' => 'Sciences d\'observation', 'cat' => 'sciences', 'coeffs' => [1, 2, 2, 2]],
        ['code' => 'HIS', 'nom' => 'Histoire', 'cat' => 'eveil', 'coeffs' => [0, 1, 2, 2]],
        ['code' => 'GEO', 'nom' => 'Géographie', 'cat' => 'eveil', 'coeffs' => [0, 1, 2, 2]],
        ['code' => 'ECM', 'nom' => 'Éducation civique', 'cat' => 'eveil', 'coeffs' => [1, 1, 1, 1]],
        ['code' => 'EPS', 'nom' => 'Éducation physique', 'cat' => 'eps', 'coeffs' => [1, 1, 1, 1]],
        ['code' => 'DES', 'nom' => 'Dessin', 'cat' => 'arts', 'coeffs' => [1, 1, 1, 1]],
        ['code' => 'CHA', 'nom' => 'Chant', 'cat' => 'arts', 'coeffs' => [1, 1, 1, 1]],
    ];

    /**
     * Matières maternelle (évaluation par compétences)
     */
    const DOMAINES_MATERNELLE = [
        ['code' => 'LAN', 'nom' => 'Langage', 'cat' => 'communication'],
        ['code' => 'MOT', 'nom' => 'Motricité', 'cat' => 'eps'],
        ['code' => 'ART', 'nom' => 'Activités artistiques', 'cat' => 'arts'],
        ['code' => 'DEC', 'nom' => 'Découverte du monde', 'cat' => 'eveil'],
        ['code' => 'VIV', 'nom' => 'Vivre ensemble', 'cat' => 'eveil'],
        ['code' => 'PRE', 'nom' => 'Pré-mathématiques', 'cat' => 'sciences'],
    ];

    /**
     * Notes associées
     */
    public function grades()
    {
        return $this->hasMany(GradeMP::class, 'subject_id');
    }

    /**
     * Compétences (pour maternelle)
     */
    public function competences()
    {
        return $this->hasMany(CompetenceMP::class, 'subject_id');
    }

    /**
     * Obtenir le coefficient pour un niveau donné
     */
    public function getCoefficientForLevel(string $niveau): int
    {
        if (Schema::connection($this->connection)->hasColumn($this->table, 'coefficient_maternelle')) {
            if (in_array($niveau, ['PS', 'MS', 'GS'])) {
                return $this->coefficient_maternelle ?? 1;
            }

            if (in_array($niveau, ['CP', 'CE1'])) {
                return $this->coefficient_cp_ce1 ?? 1;
            }

            if ($niveau === 'CE2') {
                return $this->coefficient_ce2 ?? 1;
            }

            if (in_array($niveau, ['CM1', 'CM2'])) {
                return $this->coefficient_cm1_cm2 ?? 1;
            }
        }

        if (Schema::connection($this->connection)->hasColumn($this->table, 'coefficient')) {
            return (int) ($this->coefficient ?? 1);
        }

        return 1;
    }

    /**
     * Est applicable à un niveau
     */
    public function isApplicableTo(string $niveau): bool
    {
        return $this->getCoefficientForLevel($niveau) > 0;
    }

    /**
     * Utilise l'évaluation par compétences
     */
    public function usesCompetences(): bool
    {
        return $this->type_evaluation === 'competence' || $this->type_evaluation === 'mixte';
    }

    /**
     * Libellé de la catégorie
     */
    public function getCategorieNameAttribute(): string
    {
        return self::CATEGORIES[$this->categorie] ?? $this->categorie;
    }

    /**
     * Scope: actives uniquement
     */
    public function scopeActive($query)
    {
        if (Schema::connection($this->connection)->hasColumn($this->table, 'is_active')) {
            return $query->where('is_active', true);
        }

        return $query;
    }

    /**
     * Scope: par catégorie
     */
    public function scopeByCategorie($query, string $categorie)
    {
        if (Schema::connection($this->connection)->hasColumn($this->table, 'categorie')) {
            return $query->where('categorie', $categorie);
        }

        return $query;
    }

    /**
     * Scope: pour un niveau (avec coeff > 0)
     */
    public function scopeForLevel($query, string $niveau)
    {
        if (Schema::connection($this->connection)->hasColumn($this->table, 'coefficient_maternelle')) {
            if (in_array($niveau, ['PS', 'MS', 'GS'])) {
                return $query->where('coefficient_maternelle', '>', 0);
            }
            if (in_array($niveau, ['CP', 'CE1'])) {
                return $query->where('coefficient_cp_ce1', '>', 0);
            }
            if ($niveau === 'CE2') {
                return $query->where('coefficient_ce2', '>', 0);
            }
            return $query->where('coefficient_cm1_cm2', '>', 0);
        }

        if (Schema::connection($this->connection)->hasColumn($this->table, 'niveau_minimum') && Schema::connection($this->connection)->hasColumn($this->table, 'niveau_maximum')) {
            $levels = ['PS', 'MS', 'GS', 'CP', 'CE1', 'CE2', 'CM1', 'CM2'];
            $index = array_search($niveau, $levels, true);
            if ($index === false) {
                return $query;
            }

            $minLevels = array_slice($levels, 0, $index + 1);
            $maxLevels = array_slice($levels, $index);

            $query->whereIn('niveau_minimum', $minLevels)->whereIn('niveau_maximum', $maxLevels);
        }

        if (Schema::connection($this->connection)->hasColumn($this->table, 'coefficient')) {
            $query->where('coefficient', '>', 0);
        }

        return $query;
    }
}
