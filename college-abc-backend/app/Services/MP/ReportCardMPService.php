<?php

namespace App\Services\MP;

use App\Models\MP\AnalysisMP;
use App\Models\MP\AttendanceMP;
use App\Models\MP\ClassMP;
use App\Models\MP\GradeMP;
use App\Models\MP\ReportCardMP;
use App\Models\MP\StudentMP;
use App\Models\MP\SubjectMP;
use App\Models\SchoolYear;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Service de génération des bulletins pour le Maternelle/Primaire
 */
class ReportCardMPService
{
    protected $calculator;

    public function __construct()
    {
        // On utilise le calculateur centralisé (Burkina Standard)
        // Note: Pour le primaire, le standard est souvent une moyenne simple des notes ramenées sur 10 ou 20
        // Le provider SchoolDatabaseProvider fournit 'burkina.grading' qui fait une moyenne pondérée.
        $this->calculator = app('burkina.grading');
    }

    /**
     * Génère le bulletin pour un élève donné
     */
    public function generateForStudent(string $studentId, string $classId, string $yearId, string $trimestre)
    {
        // 1. Récupérer les infos contextuelles
        $classe = ClassMP::findOrFail($classId);
        $niveau = $classe->niveau; // Ex: CP, CM2

        // 2. Récupérer les notes de l'élève pour le trimestre
        $grades = GradeMP::where('student_id', $studentId)
            ->where('class_id', $classId)
            ->where('school_year_id', $yearId)
            ->where('trimestre', $trimestre)
            ->where('is_published', true)
            ->with(['subject'])
            ->get();

        if ($grades->isEmpty()) {
            Log::warning("Aucune note pour l'élève {$studentId} au trimestre {$trimestre}");
            return null;
        }

        // 3. Regrouper par matière
        $gradesBySubject = $grades->groupBy('subject_id');
        $dataMatieres = [];
        $totalPoints = 0;
        $totalCoeffs = 0;

        foreach ($gradesBySubject as $subjectId => $subjectGrades) {
            $subject = $subjectGrades->first()->subject;

            // Si la matière n'existe plus (supprimée ?), on skip
            if (!$subject) {
                // Essai de chargement manuel si la relation n'a pas chargé
                $subject = SubjectMP::find($subjectId);
            }
            if (!$subject) continue;

            // Récupérer le coefficient correct pour le niveau de la classe
            $coeff = $subject->getCoefficientForLevel($niveau);

            // Calcul de la moyenne de la matière (sur 10 ou 20 selon la config locale, ici on voit sur 20 dans le modèle GradeMP)
            // GradeMP calcule automatiquement note_sur_20.
            // On fait la moyenne arithmétique des notes de la matière (toutes rapportées sur 20)
            $moyenneMatiere = $subjectGrades->avg('note_sur_20');
            $moyenneMatiere = round($moyenneMatiere, 2);

            $pointsMatiere = $moyenneMatiere * $coeff;

            $dataMatieres[] = [
                'subject_id' => $subject->id,
                'code' => $subject->code,
                'nom' => $subject->nom,
                'category' => $subject->categorie,
                'moyenne' => $moyenneMatiere,
                'coefficient' => $coeff,
                'points' => $pointsMatiere,
                'details' => $subjectGrades->map(fn($g) => [
                    'note' => $g->note_sur_20,
                    'type' => $g->type_evaluation,
                    'date' => $g->date_evaluation
                ])->toArray()
            ];

            $totalPoints += $pointsMatiere;
            $totalCoeffs += $coeff;
        }

        // 4. Calcul Moyenne Générale
        $moyenneGenerale = 0;
        if ($totalCoeffs > 0) {
            $moyenneGenerale = round($totalPoints / $totalCoeffs, 2);
        }

        // 5. Récupérer Absences/Retards
        $absences = AttendanceMP::where('student_id', $studentId)
            ->where('type', 'absence')
            // Optionnel: filtrer par dates du trimestre si on avait les dates de début/fin
            ->count();

        $retards = AttendanceMP::where('student_id', $studentId)
            ->where('type', 'retard')
            ->count();

        // 6. Créer ou Mettre à jour le Bulletin
        $bulletin = ReportCardMP::updateOrCreate(
            [
                'student_id' => $studentId,
                'class_id' => $classId,
                'school_year_id' => $yearId,
                'trimestre' => $trimestre,
            ],
            [
                'moyenne_generale' => $moyenneGenerale,
                'total_points' => $totalPoints,
                'total_coefficients' => $totalCoeffs,
                'data_matieres' => $dataMatieres,

                // Stats pré-calculées à 0 (seront mises à jour par updateClassStats)
                'effectif_classe' => 0,
                'moyenne_classe' => 0,
                'moyenne_premier' => 0,
                'moyenne_dernier' => 0,
                'rang' => 0,

                'absences_non_justifiees' => $absences, // Simplification pour l'instant
                'retards' => $retards,
                'mention' => $this->getMention($moyenneGenerale),
                'decision' => $trimestre == '3' ? $this->getDecisionAnnuelle($moyenneGenerale) : null,

                'is_published' => false, // Par défaut non publié
            ]
        );

        return $bulletin;
    }

    /**
     * Met à jour les statistiques de classe (rangs, moyennes min/max)
     * À appeler après avoir généré tous les bulletins d'une classe.
     */
    public function updateClassStats(string $classId, string $yearId, string $trimestre)
    {
        $bulletins = ReportCardMP::where('class_id', $classId)
            ->where('school_year_id', $yearId)
            ->where('trimestre', $trimestre)
            ->orderByDesc('moyenne_generale')
            ->get();

        if ($bulletins->isEmpty()) return;

        $effectif = $bulletins->count();
        $moyenneClasse = round($bulletins->avg('moyenne_generale'), 2);
        $min = $bulletins->min('moyenne_generale');
        $max = $bulletins->max('moyenne_generale');

        $rank = 1;
        $prevScore = -1;
        $realRank = 1;

        foreach ($bulletins as $bulletin) {
            // Gestion des ex-aequo simple ou rang strict
            // Ici rang strict simple

            $bulletin->update([
                'effectif_classe' => $effectif,
                'moyenne_classe' => $moyenneClasse,
                'moyenne_premier' => $max,
                'moyenne_dernier' => $min,
                'rang' => $rank++
            ]);
        }
    }

    private function getMention(float $note): string
    {
        if ($note >= 18) return 'Excellrent';
        if ($note >= 16) return 'Très Bien';
        if ($note >= 14) return 'Bien';
        if ($note >= 12) return 'Assez Bien';
        if ($note >= 10) return 'Passable';
        return 'Insuffisant';
    }

    private function getDecisionAnnuelle(float $moyenne): string
    {
        if ($moyenne >= 10) return 'passage';
        if ($moyenne >= 9) return 'conditionnel'; // ou examen de rattrapage
        return 'redoublement';
    }
}
