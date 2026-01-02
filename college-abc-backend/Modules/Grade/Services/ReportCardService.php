<?php

namespace Modules\Grade\Services;

use Exception;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Modules\Academic\Entities\ClassRoom;
use Modules\Academic\Entities\Semester;
use Modules\Grade\Entities\Grade;
use Modules\Student\Entities\Student;
use Modules\Grade\Repositories\GradeRepository;

class ReportCardService
{
    protected $gradeRepository;

    public function __construct(GradeRepository $gradeRepository)
    {
        $this->gradeRepository = $gradeRepository;
    }

    /**
     * Calcule le classement complet pour une classe donnée sur une période (ex: trimestre).
     */
    public function generateClassReport(int $classId, int $academicYearId, int $periodId = null): array
    {
        $classroom = ClassRoom::findOrFail($classId);

        // 1. Récupérer les élèves inscrits
        $students = Student::whereHas('enrollments', function ($q) use ($classId, $academicYearId) {
            $q->where('class_id', $classId)
                ->where('academic_year_id', $academicYearId);
        })->get();

        // 2. Calculer les moyennes individuelles
        $studentReports = $students->map(function ($student) use ($academicYearId, $periodId) {
            return $this->calculateStudentAverages($student->id, $academicYearId, $periodId);
        });

        // 3. Trier par moyenne générale décroissante
        $rankedReports = $studentReports->sortByDesc('general_average')->values();

        // 4. Assigner les rangs
        $rankedReports = $this->assignRanks($rankedReports);

        return [
            'class_id' => $classId,
            'class_name' => $classroom->name,
            'academic_year_id' => $academicYearId,
            'period_id' => $periodId,
            'total_students' => $students->count(),
            'class_average' => $rankedReports->avg('general_average'),
            'students' => $rankedReports
        ];
    }

    /**
     * Calcule la moyenne et les détails par matière pour un élève.
     */
    public function calculateStudentAverages(int $studentId, int $academicYearId, int $periodId = null): array
    {
        // Récupérer la période si spécifiée pour filtrer par date
        $semester = null;
        if ($periodId) {
            $semester = Semester::find($periodId);
        }

        // Récupérer toutes les notes de l'élève
        $query = Grade::query()
            ->with(['evaluation.subject'])
            ->where('student_id', $studentId)
            ->where('is_absent', false) // On ne compte pas les absences dans la moyenne (sauf si note est 0, mais is_absent flag true = ignored)
            ->whereHas('evaluation', function ($q) use ($academicYearId, $semester) {
                $q->where('academic_year_id', $academicYearId);

                // Filtrer par dates de la période si disponible
                if ($semester) {
                    $q->whereBetween('evaluation_date', [$semester->start_date, $semester->end_date]);
                }
            });

        $grades = $query->get();

        if ($grades->isEmpty()) {
            return [
                'student_id' => $studentId,
                'student' => Student::find($studentId), // Optimization: Pass student object if possible to avoid N+1
                'general_average' => 0,
                'total_points' => 0,
                'total_coefficients' => 0,
                'subjects' => []
            ];
        }

        // Grouper par matière
        $gradesBySubject = $grades->groupBy(fn($g) => $g->evaluation->subject_id);

        $subjectAverages = [];
        $totalGeneralPoints = 0;
        $totalGeneralCoefficients = 0;

        foreach ($gradesBySubject as $subjectId => $subjectGrades) {
            $subject = $subjectGrades->first()->evaluation->subject;

            // Calcul de la moyenne de la matière
            // Formule: Somme(Note * Coef_eval) / Somme(Coef_eval)
            $sumWeightedScores = 0;
            $sumEvalCoefficients = 0;

            foreach ($subjectGrades as $grade) {
                $evalCoef = $grade->evaluation->coefficient ?? 1;
                $sumWeightedScores += ($grade->score * $evalCoef);
                $sumEvalCoefficients += $evalCoef;
            }

            $subjectAverage = $sumEvalCoefficients > 0 ? ($sumWeightedScores / $sumEvalCoefficients) : 0;

            // Coefficient de la matière pour la moyenne générale
            $subjectCoefficient = $subject->coefficient ?? 1;

            $subjectAverages[$subjectId] = [
                'subject_name' => $subject->name,
                'average' => round($subjectAverage, 2),
                'coefficient' => $subjectCoefficient,
                'points' => $subjectAverage * $subjectCoefficient,
                'grades' => $subjectGrades->map(fn($g) => [
                    'score' => $g->score,
                    'type' => $g->evaluation->type,
                    'is_absent' => $g->is_absent
                ])->values()
            ];

            $totalGeneralPoints += ($subjectAverage * $subjectCoefficient);
            $totalGeneralCoefficients += $subjectCoefficient;
        }

        $generalAverage = $totalGeneralCoefficients > 0 ? ($totalGeneralPoints / $totalGeneralCoefficients) : 0;

        return [
            'student_id' => $studentId,
            'student' => Student::find($studentId),
            'general_average' => round($generalAverage, 2),
            'total_points' => round($totalGeneralPoints, 2),
            'total_coefficients' => $totalGeneralCoefficients,
            'subjects' => collect($subjectAverages)->values() // Return array list not keyed map
        ];
    }

    /**
     * Assigne les rangs (1er, 2ème, 2ème ex-aequo, 4ème...).
     */
    private function assignRanks(Collection $reports): Collection
    {
        $rankedReports = $reports->toArray(); // Array of arrays
        $rank = 1;

        foreach ($rankedReports as $index => &$report) {
            if ($index > 0 && $report['general_average'] == $rankedReports[$index - 1]['general_average']) {
                $report['rank'] = $rankedReports[$index - 1]['rank'];
                $report['rank_suffix'] = 'ex';
            } else {
                $report['rank'] = $index + 1;
                $report['rank_suffix'] = ($report['rank'] == 1) ? 'er' : 'ème';
            }
        }

        return collect($rankedReports);
    }
}
