<?php

namespace App\Models\MP;

use App\Models\SchoolYear;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

/**
 * Modèle ReportCardMP - Bulletins Maternelle/Primaire
 * Base de données: school_maternelle_primaire
 * 
 * Format officiel burkinabè avec:
 * - Calcul automatique des moyennes (arrondi 2 décimales)
 * - Mentions automatiques
 * - Décisions de passage
 */
class ReportCardMP extends Model
{
    use HasUuids;

    protected $connection = 'school_mp';
    protected $table = 'report_cards_mp';

    protected $fillable = [
        'student_id',
        'class_id',
        'school_year_id',
        'trimestre',
        'moyenne_generale',
        'total_points',
        'total_coefficients',
        'rang',
        'effectif_classe',
        'moyenne_classe',
        'moyenne_premier',
        'moyenne_dernier',
        'absences_justifiees',
        'absences_non_justifiees',
        'retards',
        'mention',
        'appreciation_generale',
        'decision',
        'data_matieres',
        'is_validated',
        'validated_by',
        'validated_at',
        'pdf_path',
    ];

    protected $casts = [
        'moyenne_generale' => 'decimal:2',
        'total_points' => 'decimal:2',
        'total_coefficients' => 'integer',
        'rang' => 'integer',
        'effectif_classe' => 'integer',
        'moyenne_classe' => 'decimal:2',
        'moyenne_premier' => 'decimal:2',
        'moyenne_dernier' => 'decimal:2',
        'absences_justifiees' => 'integer',
        'absences_non_justifiees' => 'integer',
        'retards' => 'integer',
        'data_matieres' => 'array',
        'is_validated' => 'boolean',
        'validated_at' => 'datetime',
    ];

    /**
     * Mentions selon cahier des charges
     */
    const MENTION_EXCELLENT = 'excellent';       // >= 18
    const MENTION_TRES_BIEN = 'tres_bien';       // 16-17.99
    const MENTION_BIEN = 'bien';                 // 14-15.99
    const MENTION_ASSEZ_BIEN = 'assez_bien';     // 12-13.99
    const MENTION_PASSABLE = 'passable';         // 10-11.99
    const MENTION_INSUFFISANT = 'insuffisant';   // < 10

    /**
     * Décisions
     */
    const DECISION_PASSAGE = 'passage';
    const DECISION_REDOUBLEMENT = 'redoublement';
    const DECISION_CONDITIONNEL = 'conditionnel';

    /**
     * Calculer la mention automatique
     */
    public static function calculateMention(float $moyenne): string
    {
        if ($moyenne >= 18) return self::MENTION_EXCELLENT;
        if ($moyenne >= 16) return self::MENTION_TRES_BIEN;
        if ($moyenne >= 14) return self::MENTION_BIEN;
        if ($moyenne >= 12) return self::MENTION_ASSEZ_BIEN;
        if ($moyenne >= 10) return self::MENTION_PASSABLE;
        return self::MENTION_INSUFFISANT;
    }

    /**
     * Proposer une décision
     */
    public static function proposeDecision(float $moyenneAnnuelle): string
    {
        if ($moyenneAnnuelle >= 10) return self::DECISION_PASSAGE;
        if ($moyenneAnnuelle >= 9) return self::DECISION_CONDITIONNEL;
        return self::DECISION_REDOUBLEMENT;
    }

    /**
     * Générer le bulletin
     */
    public function generate(): void
    {
        // Récupérer toutes les notes du trimestre
        $grades = GradeMP::where('student_id', $this->student_id)
            ->where('class_id', $this->class_id)
            ->where('school_year_id', $this->school_year_id)
            ->where('trimestre', $this->trimestre)
            ->where('is_published', true)
            ->get();

        // Grouper par matière et calculer les moyennes
        $dataM = [];
        $totalPoints = 0;
        $totalCoef = 0;

        $gradesBySubject = $grades->groupBy('subject_id');

        foreach ($gradesBySubject as $subjectId => $subjectGrades) {
            $subject = SubjectMP::find($subjectId);
            if (!$subject) continue;

            // Moyenne de la matière
            $moyenne = round($subjectGrades->avg('note_sur_20'), 2);
            $coef = $subject->coefficient;
            $points = $moyenne * $coef;

            $dataM[] = [
                'subject_id' => $subjectId,
                'subject_name' => $subject->nom,
                'coefficient' => $coef,
                'notes' => $subjectGrades->pluck('note_sur_20')->toArray(),
                'moyenne' => $moyenne,
                'points' => $points,
            ];

            $totalPoints += $points;
            $totalCoef += $coef;
        }

        // Calculer la moyenne générale
        $moyenneGenerale = $totalCoef > 0 ? round($totalPoints / $totalCoef, 2) : 0;

        // Compter les absences
        $attendances = AttendanceMP::where('student_id', $this->student_id)
            ->whereYear('date', now()->year)
            ->get();

        // Mettre à jour le bulletin
        $this->data_matieres = $dataM;
        $this->total_points = round($totalPoints, 2);
        $this->total_coefficients = $totalCoef;
        $this->moyenne_generale = $moyenneGenerale;
        $this->mention = self::calculateMention($moyenneGenerale);
        $this->absences_justifiees = $attendances->where('type', 'absence')->where('statut', 'justifiee')->count();
        $this->absences_non_justifiees = $attendances->where('type', 'absence')->where('statut', 'non_justifiee')->count();
        $this->retards = $attendances->where('type', 'retard')->count();

        $this->save();
    }

    /**
     * Calculer le rang et les statistiques de classe
     */
    public function calculateClassStats(): void
    {
        // Récupérer tous les bulletins de la classe
        $bulletins = self::where('class_id', $this->class_id)
            ->where('school_year_id', $this->school_year_id)
            ->where('trimestre', $this->trimestre)
            ->orderByDesc('moyenne_generale')
            ->get();

        $this->effectif_classe = $bulletins->count();
        $this->moyenne_classe = round($bulletins->avg('moyenne_generale'), 2);
        $this->moyenne_premier = $bulletins->max('moyenne_generale');
        $this->moyenne_dernier = $bulletins->min('moyenne_generale');

        // Calculer le rang
        $rang = 1;
        foreach ($bulletins as $b) {
            if ($b->id === $this->id) {
                $this->rang = $rang;
                break;
            }
            $rang++;
        }

        $this->save();
    }

    /**
     * Valider le bulletin
     */
    public function validate(string $validatorId): void
    {
        $this->is_validated = true;
        $this->validated_by = $validatorId;
        $this->validated_at = now();
        $this->save();
    }

    /**
     * Relations
     */
    public function student()
    {
        return $this->belongsTo(StudentMP::class, 'student_id');
    }

    public function class()
    {
        return $this->belongsTo(ClassMP::class, 'class_id');
    }

    public function schoolYear()
    {
        return $this->belongsTo(SchoolYear::class);
    }

    /**
     * Scopes
     */
    public function scopeValidated($query)
    {
        return $query->where('is_validated', true);
    }

    public function scopeByTrimestre($query, string $trimestre)
    {
        return $query->where('trimestre', $trimestre);
    }
}
