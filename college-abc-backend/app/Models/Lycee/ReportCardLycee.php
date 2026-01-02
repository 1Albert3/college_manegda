<?php

namespace App\Models\Lycee;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

use App\Models\SchoolYear;

/**
 * Modèle ReportCardLycee - Bulletin Lycée
 * Conforme au standard Burkinabé avec gestion Séries
 */
class ReportCardLycee extends Model
{
    use HasUuids;

    protected $connection = 'school_lycee';
    protected $table = 'report_cards_lycee';

    protected $fillable = [
        'student_id',
        'class_id',
        'school_year_id',
        'trimestre', // 1, 2, 3

        // Données JSON
        'data_matieres', // Détail des notes et coeffs par matière
        // Exemple Structure: 
        // [{code: 'MAT', nom: 'Maths', moyenne: 12.5, coef: 5, points: 62.5, rang: 3, appreciation: 'Bien'}]

        // Totaux
        'total_points',
        'total_coefficients',
        'moyenne_generale',
        'effectif_classe',
        'moyenne_classe',
        'moyenne_premier',
        'moyenne_dernier',
        'rang',

        // Discipline & Décision
        'absences_justifiees',
        'absences_non_justifiees',
        'retards',
        'conduite', // Note de conduite / Avertissement / Blâme
        'tableau_honneur', // Félicitations, Encouragements...
        'decision_conseil', // Passage, Redoublement (surtout T3)

        'is_validated',
        'validated_at',
        'validated_by',
        'pdf_path'
    ];

    protected $casts = [
        'data_matieres' => 'array',
        'total_points' => 'float',
        'total_coefficients' => 'integer',
        'moyenne_generale' => 'float',
        'moyenne_classe' => 'float',
        'moyenne_premier' => 'float',
        'moyenne_dernier' => 'float',
        'rang' => 'integer',
        'is_validated' => 'boolean',
        'validated_at' => 'datetime'
    ];

    public function student()
    {
        return $this->belongsTo(StudentLycee::class, 'student_id');
    }

    public function class()
    {
        return $this->belongsTo(ClassLycee::class, 'class_id');
    }

    public function schoolYear()
    {
        return $this->belongsTo(SchoolYear::class);
    }
}
