<?php

namespace App\Services;

use App\Models\MP\ClassMP;
use App\Models\MP\GradeMP;
use App\Models\MP\ReportCardMP;
use App\Models\MP\StudentMP;
use App\Models\MP\SubjectMP;
use App\Models\MP\AttendanceMP;
use App\Models\SchoolYear;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Storage;

/**
 * Service de génération des bulletins scolaires
 * Format officiel burkinabè
 * 
 * Conforme aux exigences:
 * - Calculs avec arrondi 2 décimales
 * - Mentions automatiques
 * - Statistiques de classe
 * - Format PDF officiel
 */
class ReportCardService
{
    /**
     * Générer les bulletins pour une classe et un trimestre
     */
    public function generateForClass(string $classId, string $trimestre): array
    {
        $class = ClassMP::with('teacher')->findOrFail($classId);
        $schoolYear = SchoolYear::current();

        if (!$schoolYear) {
            throw new \Exception('Aucune année scolaire en cours.');
        }

        // Récupérer tous les élèves inscrits
        $students = $class->students()->get();

        if ($students->isEmpty()) {
            throw new \Exception('Aucun élève inscrit dans cette classe.');
        }

        $bulletins = [];

        // Générer le bulletin pour chaque élève
        foreach ($students as $student) {
            $bulletin = $this->generateBulletin(
                $student->id,
                $classId,
                $trimestre,
                $schoolYear->id
            );
            $bulletins[] = $bulletin;
        }

        // Calculer les statistiques de classe
        $this->calculateClassStatistics($bulletins);

        return $bulletins;
    }

    /**
     * Générer le bulletin pour un élève
     */
    public function generateBulletin(
        string $studentId,
        string $classId,
        string $trimestre,
        string $schoolYearId
    ): ReportCardMP {
        $student = StudentMP::findOrFail($studentId);
        $class = ClassMP::findOrFail($classId);

        // Vérifier si le bulletin existe déjà
        $bulletin = ReportCardMP::where('student_id', $studentId)
            ->where('class_id', $classId)
            ->where('school_year_id', $schoolYearId)
            ->where('trimestre', $trimestre)
            ->first();

        if (!$bulletin) {
            $bulletin = new ReportCardMP([
                'student_id' => $studentId,
                'class_id' => $classId,
                'school_year_id' => $schoolYearId,
                'trimestre' => $trimestre,
            ]);
        }

        // Récupérer les notes publiées
        $grades = GradeMP::where('student_id', $studentId)
            ->where('class_id', $classId)
            ->where('school_year_id', $schoolYearId)
            ->where('trimestre', $trimestre)
            ->where('is_published', true)
            ->get();

        // Calculer les moyennes par matière
        $calculator = app('burkina.grading');
        $dataMatiere = [];
        $totalPoints = 0;
        $totalCoef = 0;

        $gradesBySubject = $grades->groupBy('subject_id');

        foreach ($gradesBySubject as $subjectId => $subjectGrades) {
            $subject = SubjectMP::find($subjectId);
            if (!$subject) continue;

            $notes = $subjectGrades->pluck('note_sur_20')->toArray();
            $coeffsDevoirs = array_fill(0, count($notes), 1);

            $moyenne = $calculator->calculateAverage($notes, $coeffsDevoirs);
            $coefMatiere = (int) $subject->getCoefficientForLevel($class->niveau);
            $points = round($moyenne * $coefMatiere, 2);

            $dataMatiere[] = [
                'subject_id' => $subjectId,
                'code' => $subject->code,
                'nom' => $subject->nom,
                'coefficient' => $coefMatiere,
                'notes' => $subjectGrades->map(function ($g) {
                    return [
                        'type' => $g->type_evaluation,
                        'note' => $g->note_sur_20,
                        'date' => $g->date_evaluation->format('d/m/Y'),
                    ];
                })->toArray(),
                'moyenne' => $moyenne,
                'points' => $points,
            ];

            $totalPoints += $points;
            $totalCoef += $coefMatiere;
        }

        $moyenneGenerale = $totalCoef > 0 ? round($totalPoints / $totalCoef, 2) : 0;
        $absences = $this->getAbsencesForTrimestre($studentId, $trimestre, $schoolYearId);

        $bulletin->fill([
            'data_matieres' => $dataMatiere,
            'total_points' => round($totalPoints, 2),
            'total_coefficients' => $totalCoef,
            'moyenne_generale' => $moyenneGenerale,
            'mention' => $this->calculateMention($moyenneGenerale),
            'absences_justifiees' => $absences['justifiees'],
            'absences_non_justifiees' => $absences['non_justifiees'],
            'retards' => $absences['retards'],
            'effectif_classe' => $class->effectif_actuel ?? 0,

            'moyenne_classe' => 0,
            'moyenne_premier' => 0,
            'moyenne_dernier' => 0,
            'rang' => 0,
        ]);

        $bulletin->save();

        // Générer le PDF physique
        $this->generatePDF($bulletin);

        return $bulletin;
    }

    /**
     * Calculer les statistiques de classe
     */
    public function calculateClassStatistics(array $bulletins): void
    {
        if (empty($bulletins)) return;

        $moyennes = array_map(function ($b) {
            return $b->moyenne_generale;
        }, $bulletins);

        $effectif = count($bulletins);
        $moyenneClasse = round(array_sum($moyennes) / $effectif, 2);
        $moyennePremier = max($moyennes);
        $moyenneDernier = min($moyennes);

        // Trier par moyenne décroissante
        usort($bulletins, function ($a, $b) {
            return $b->moyenne_generale <=> $a->moyenne_generale;
        });

        // Attribuer les rangs
        $rang = 1;
        foreach ($bulletins as $bulletin) {
            $bulletin->rang = $rang;
            $bulletin->effectif_classe = $effectif;
            $bulletin->moyenne_classe = $moyenneClasse;
            $bulletin->moyenne_premier = $moyennePremier;
            $bulletin->moyenne_dernier = $moyenneDernier;
            $bulletin->save();
            $rang++;
        }
    }

    /**
     * Générer le PDF du bulletin
     */
    public function generatePDF(ReportCardMP $bulletin)
    {
        $bulletin->load(['student', 'class', 'schoolYear']);

        $data = [
            'bulletin' => $bulletin,
            'student' => $bulletin->student,
            'class' => $bulletin->class,
            'schoolYear' => $bulletin->schoolYear,
            'school_year' => $bulletin->schoolYear?->name,
            'semester' => $bulletin->trimestre,
            'grades' => $bulletin->data_matieres,
            'matieres' => $bulletin->data_matieres, // Compatibilité template
            'averages' => [
                'total_coefficients' => $bulletin->total_coefficients,
                'total_weighted_sum' => $bulletin->total_points,
                'general_average' => $bulletin->moyenne_generale,
                'rank' => $bulletin->rang,
                'class_size' => $bulletin->effectif_classe
            ],
            'school_info' => [
                'name' => config('app.school_name', 'WEND-MANEGDA')
            ],
            'etablissement' => [
                'nom' => config('app.school_name', 'WEND-MANEGDA')
            ]
        ];

        $pdf = Pdf::loadView('pdf.bulletin', $data);
        $pdf->setPaper('A4', 'portrait');

        $filename = "bulletins/mp/{$bulletin->school_year_id}/T{$bulletin->trimestre}/{$bulletin->student->matricule}.pdf";

        Storage::disk('public')->put($filename, $pdf->output());

        $bulletin->pdf_path = $filename;
        $bulletin->save();

        return $filename;
    }

    /**
     * Calculer la mention selon les seuils burkinabè
     */
    private function calculateMention(float $moyenne): string
    {
        if ($moyenne >= 18) return 'excellent';
        if ($moyenne >= 16) return 'tres_bien';
        if ($moyenne >= 14) return 'bien';
        if ($moyenne >= 12) return 'assez_bien';
        if ($moyenne >= 10) return 'passable';
        return 'insuffisant';
    }

    /**
     * Proposer la décision de passage
     */
    public function proposeDecision(float $moyenneAnnuelle): string
    {
        if ($moyenneAnnuelle >= 10) return 'passage';
        if ($moyenneAnnuelle >= 9) return 'conditionnel';
        return 'redoublement';
    }

    /**
     * Récupérer les absences pour un trimestre
     */
    private function getAbsencesForTrimestre(
        string $studentId,
        string $trimestre,
        string $schoolYearId
    ): array {
        // Déterminer les dates du trimestre
        $schoolYear = SchoolYear::find($schoolYearId);
        $dates = $this->getTrimestreDates($schoolYear, $trimestre);

        $attendances = AttendanceMP::where('student_id', $studentId)
            ->whereBetween('date', [$dates['start'], $dates['end']])
            ->get();

        return [
            'justifiees' => $attendances->where('type', 'absence')
                ->where('statut', 'justifiee')->count(),
            'non_justifiees' => $attendances->where('type', 'absence')
                ->where('statut', 'non_justifiee')->count(),
            'retards' => $attendances->where('type', 'retard')->count(),
        ];
    }

    /**
     * Obtenir les dates d'un trimestre
     */
    private function getTrimestreDates(SchoolYear $schoolYear, string $trimestre): array
    {
        $year = intval(substr($schoolYear->name, 0, 4));

        switch ($trimestre) {
            case '1':
                return [
                    'start' => "{$year}-09-01",
                    'end' => "{$year}-12-15",
                ];
            case '2':
                return [
                    'start' => ($year + 1) . "-01-05",
                    'end' => ($year + 1) . "-03-31",
                ];
            case '3':
                return [
                    'start' => ($year + 1) . "-04-01",
                    'end' => ($year + 1) . "-06-30",
                ];
            default:
                return [
                    'start' => $schoolYear->start_date,
                    'end' => $schoolYear->end_date,
                ];
        }
    }

    /**
     * Générer le bulletin annuel
     */
    public function generateAnnualReport(string $studentId, string $classId, string $schoolYearId): array
    {
        // Récupérer les 3 bulletins trimestriels
        $bulletins = ReportCardMP::where('student_id', $studentId)
            ->where('class_id', $classId)
            ->where('school_year_id', $schoolYearId)
            ->orderBy('trimestre')
            ->get();

        if ($bulletins->count() < 3) {
            throw new \Exception('Les 3 bulletins trimestriels sont nécessaires pour le bulletin annuel.');
        }

        // Calculer la moyenne annuelle
        $moyenneAnnuelle = round($bulletins->avg('moyenne_generale'), 2);

        // Décision de passage
        $decision = $this->proposeDecision($moyenneAnnuelle);

        return [
            'student_id' => $studentId,
            'school_year_id' => $schoolYearId,
            'bulletins' => $bulletins,
            'moyenne_t1' => $bulletins[0]->moyenne_generale,
            'moyenne_t2' => $bulletins[1]->moyenne_generale,
            'moyenne_t3' => $bulletins[2]->moyenne_generale,
            'moyenne_annuelle' => $moyenneAnnuelle,
            'decision' => $decision,
            'mention' => $this->calculateMention($moyenneAnnuelle),
        ];
    }
}
