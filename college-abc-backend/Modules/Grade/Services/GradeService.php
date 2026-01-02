<?php

namespace Modules\Grade\Services;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Modules\Grade\Entities\Grade;
use Modules\Grade\Entities\Evaluation;
use Modules\Grade\Repositories\GradeRepository;
use Modules\Grade\Repositories\EvaluationRepository;
use Modules\Student\Entities\Student;
use Modules\Academic\Entities\AcademicYear;
use Exception;

class GradeService
{
    protected $gradeRepository;
    protected $evaluationRepository;

    public function __construct(
        GradeRepository $gradeRepository,
        EvaluationRepository $evaluationRepository
    ) {
        $this->gradeRepository = $gradeRepository;
        $this->evaluationRepository = $evaluationRepository;
    }

    // CRUD operations
    public function createGrade(array $data): Grade
    {
        try {
            // Validation des données
            $this->validateGradeData($data);

            // Vérifier si la note existe déjà
            if ($this->gradeRepository->studentHasGradeForEvaluation(
                $data['student_id'],
                $data['evaluation_id']
            )) {
                throw new Exception('Une note existe déjà pour cet élève dans cette évaluation.');
            }

            $grade = $this->gradeRepository->create($data);

            Log::info('Grade created', [
                'grade_id' => $grade->id,
                'student_id' => $grade->student_id,
                'evaluation_id' => $grade->evaluation_id,
                'score' => $grade->score
            ]);

            return $grade;
        } catch (Exception $e) {
            Log::error('Failed to create grade', [
                'data' => $data,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    public function updateGrade(Grade $grade, array $data): Grade
    {
        try {
            $this->validateGradeData($data, $grade->id);

            $oldScore = $grade->score;
            $updated = $this->gradeRepository->update($grade, $data);

            if ($updated) {
                Log::info('Grade updated', [
                    'grade_id' => $grade->id,
                    'old_score' => $oldScore,
                    'new_score' => $data['score'] ?? $grade->fresh()->score
                ]);
            }

            return $grade->fresh();
        } catch (Exception $e) {
            Log::error('Failed to update grade', [
                'grade_id' => $grade->id,
                'data' => $data,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    public function recordGrade(array $data, $userId): Grade
    {
        DB::beginTransaction();
        try {
            $data['recorded_by'] = $userId;
            $data['recorded_at'] = now();

            // Vérifier les permissions
            if (!$this->canRecordGrade($data['evaluation_id'], $data['student_id'], $userId)) {
                throw new Exception('Vous n\'avez pas la permission d\'enregistrer cette note.');
            }

            $grade = $this->createGrade($data);
            DB::commit();

            return $grade;
        } catch (Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    public function bulkRecordGrades(array $gradesData, $userId): Collection
    {
        DB::beginTransaction();
        try {
            $grades = collect();

            foreach ($gradesData as $data) {
                $data['recorded_by'] = $userId;
                $data['recorded_at'] = now();

                if (!$this->canRecordGrade($data['evaluation_id'], $data['student_id'], $userId)) {
                    throw new Exception("Permission refusée pour l'élève {$data['student_id']}.");
                }

                $grade = $this->createGrade($data);
                $grades->push($grade);
            }

            DB::commit();
            return $grades;
        } catch (Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    // Evaluation management
    public function startEvaluation(int $evaluationId): Evaluation
    {
        $evaluation = $this->evaluationRepository->findById($evaluationId);

        if (!$evaluation) {
            throw new Exception('Évaluation non trouvée.');
        }

        if (!$this->evaluationRepository->canStartEvaluation($evaluationId)) {
            throw new Exception('Cette évaluation ne peut pas être démarrée.');
        }

        $evaluation->start();
        return $evaluation;
    }

    public function completeEvaluation(int $evaluationId): Evaluation
    {
        $evaluation = $this->evaluationRepository->findById($evaluationId);

        if (!$evaluation) {
            throw new Exception('Évaluation non trouvée.');
        }

        $evaluation->complete();
        return $evaluation;
    }

    // Statistics and reporting
    public function getStudentGradesReport(int $studentId, int $academicYearId = null): array
    {
        $student = Student::find($studentId);

        if (!$student) {
            throw new Exception('Élève non trouvé.');
        }

        $grades = $this->gradeRepository->getByStudent($studentId);

        // Filter by academic year
        if ($academicYearId) {
            $grades = $grades->filter(function ($grade) use ($academicYearId) {
                return $grade->evaluation->academic_year_id === $academicYearId;
            });
        }

        $gradesBySubject = $grades->groupBy(function ($grade) {
            return $grade->evaluation->subject->name;
        });

        $subjectReports = [];
        $overallStats = [
            'total_grades' => $grades->count(),
            'present_grades' => $grades->where('is_absent', false)->count(),
            'absent_grades' => $grades->where('is_absent', true)->count(),
            'average_score' => 0, // Will be calculated
            'passing_rate' => 0,
        ];

        // Global average vars
        $totalWeightedSum = 0;
        $totalCoefficients = 0;

        foreach ($gradesBySubject as $subjectName => $subjectGrades) {
            $presentGrades = $subjectGrades->where('is_absent', false);

            // Calculate Subject Average
            $subjectSum = 0;
            $subjectCoefSum = 0;

            foreach ($presentGrades as $g) {
                $coef = $g->evaluation->coefficient ?? 1;
                $subjectSum += ($g->score * $coef);
                $subjectCoefSum += $coef;
            }

            $subjectAvg = $subjectCoefSum > 0 ? ($subjectSum / $subjectCoefSum) : 0;
            $subjectSubjectCoef = $subjectGrades->first()->evaluation->subject->coefficient ?? 1;

            // Add to global stats (simple average of all grades logic OR subject-weighted logic?)
            // Usually simpler stats just show average of all grades, but let's try to be consistent.
            // For simple "Grades Report", we often just dump the list.
            // Let's keep it simple: Average = Avg of all scores provided in the list (weighted by eval coef).

            // Note: This 'overall_stats.average_score' usually refers to raw grade average. 
            // Real academic average is in ReportCardService.

            $subjectReports[$subjectName] = [
                'subject' => $subjectName,
                'grades_count' => $subjectGrades->count(),
                'present_grades' => $presentGrades->count(),
                'absent_grades' => $subjectGrades->where('is_absent', true)->count(),
                'average' => round($subjectAvg, 2),
                'minimum' => $presentGrades->min('score'), // Use raw score
                'maximum' => $presentGrades->max('score'), // Use raw score
                'passing_grades' => $presentGrades->where('score', '>=', 10)->count(), // Raw score comparison
                'coefficient' => $subjectSubjectCoef,
            ];

            $totalWeightedSum += ($subjectAvg * $subjectSubjectCoef);
            $totalCoefficients += $subjectSubjectCoef;
        }

        // Use ReportCard style calculation for global average
        $overallStats['average_score'] = $totalCoefficients > 0 ? round($totalWeightedSum / $totalCoefficients, 2) : 0;

        $totalPresentGrades = $grades->where('is_absent', false);
        if ($totalPresentGrades->count() > 0) {
            // Passing rate based on raw scores >= 10
            $overallStats['passing_rate'] = round(
                ($totalPresentGrades->where('score', '>=', 10)->count() / $totalPresentGrades->count()) * 100,
                1
            );
        }

        return [
            'student' => $student,
            'academic_year' => $academicYearId ? AcademicYear::find($academicYearId) : null,
            'overall_stats' => $overallStats,
            'subjects' => $subjectReports,
            'grades' => $grades->values(),
        ];
    }

    public function getClassGradesReport(int $classId, int $academicYearId = null): array
    {
        $class = \Modules\Academic\Entities\ClassRoom::with('enrollments.student')->find($classId);

        if (!$class) {
            throw new Exception('Classe non trouvée.');
        }

        $enrolledStudents = $class->enrollments()
            ->when($academicYearId, fn($q) => $q->where('academic_year_id', $academicYearId))
            ->with('student')
            ->get()
            ->pluck('student');

        $evaluations = $this->evaluationRepository->getByClass($classId);

        if ($academicYearId) {
            $evaluations = $evaluations->where('academic_year_id', $academicYearId);
        }

        $grades = collect();
        foreach ($evaluations as $evaluation) {
            $evaluationGrades = $this->gradeRepository->getClassGradesForEvaluation($evaluation->id);
            $grades = $grades->merge($evaluationGrades);
        }

        // Statistiques par matière
        $subjectsStats = [];
        $subjectsGrouped = $evaluations->groupBy(function ($evaluation) {
            return $evaluation->subject->name;
        });

        foreach ($subjectsGrouped as $subjectName => $subjectEvaluations) {
            // Find grades for these evaluations
            $subjectGrades = $grades->filter(function ($grade) use ($subjectEvaluations) {
                return $subjectEvaluations->pluck('id')->contains($grade->evaluation_id);
            })->where('is_absent', false);

            if ($subjectGrades->count() > 0) {
                // Calculate average using weighted method
                $sumScores = 0;
                $sumCoefs = 0;
                foreach ($subjectGrades as $g) {
                    $c = $g->evaluation->coefficient ?? 1;
                    $sumScores += ($g->score * $c);
                    $sumCoefs += $c;
                }
                $avg = $sumCoefs > 0 ? ($sumScores / $sumCoefs) : 0;

                $subjectsStats[$subjectName] = [
                    'subject' => $subjectName,
                    'evaluations_count' => $subjectEvaluations->count(),
                    'grades_count' => $subjectGrades->count(),
                    'average' => round($avg, 2),
                    'passing_rate' => round(($subjectGrades->where('score', '>=', 10)->count() / $subjectGrades->count()) * 100, 1),
                ];
            }
        }

        return [
            'class' => $class,
            'enrolled_students' => $enrolledStudents,
            'total_evaluations' => $evaluations->count(),
            'total_grades' => $grades->count(),
            'subjects_stats' => $subjectsStats,
            'passing_rate' => $this->calculateClassPassingRate($grades),
        ];
    }

    public function getTeacherGradesReport(int $teacherId, int $academicYearId = null): array
    {
        $teacher = \Modules\Core\Entities\User::find($teacherId);

        if (!$teacher) {
            throw new Exception('Enseignant non trouvé.');
        }

        $evaluations = $this->evaluationRepository->getByTeacher($teacherId);

        if ($academicYearId) {
            $evaluations = $evaluations->where('academic_year_id', $academicYearId);
        }

        $stats = [
            'total_evaluations' => $evaluations->count(),
            'completed_evaluations' => $evaluations->where('status', 'completed')->count(),
            'ongoing_evaluations' => $evaluations->where('status', 'ongoing')->count(),
            'planned_evaluations' => $evaluations->where('status', 'planned')->count(),
        ];

        // Statistiques détaillées par évaluation
        $evaluationsReport = [];
        foreach ($evaluations as $evaluation) {
            $grades = $this->gradeRepository->getByEvaluation($evaluation->id);
            $presentGrades = $grades->where('is_absent', false);

            $avg = $presentGrades->avg('score'); // Simple average for single evaluation is fine as coef is constant

            $evaluationsReport[] = [
                'evaluation' => $evaluation,
                'total_grades' => $grades->count(),
                'present_grades' => $presentGrades->count(),
                'absent_grades' => $grades->where('is_absent', true)->count(),
                'average_score' => $avg ? round($avg, 2) : 0,
                'passing_rate' => $presentGrades->count() > 0
                    ? round(($presentGrades->where('score', '>=', 10)->count() / $presentGrades->count()) * 100, 1)
                    : 0,
            ];
        }

        return [
            'teacher' => $teacher,
            'stats' => $stats,
            'evaluations' => $evaluationsReport,
        ];
    }

    // Utility methods
    protected function validateGradeData(array $data, int $excludeId = null): void
    {
        $rules = [
            'student_id' => 'required|exists:students,id',
            'evaluation_id' => 'required|exists:evaluations,id',
            'score' => 'nullable|numeric|min:0|max:20',
            'coefficient' => 'nullable|numeric|min:0.1|max:5',
            'comments' => 'nullable|string|max:500',
        ];

        $validator = validator($data, $rules);

        if ($validator->fails()) {
            throw new Exception('Données invalides: ' . implode(', ', $validator->errors()->all()));
        }

        // Validation métier
        $evaluation = Evaluation::find($data['evaluation_id']);

        if (!$evaluation) {
            throw new Exception('Évaluation non trouvée.');
        }

        // Vérifier que la note est dans la plage autorisée
        if (isset($data['score'])) {
            if ($data['score'] < $evaluation->minimum_score || $data['score'] > $evaluation->maximum_score) {
                throw new Exception("La note doit être entre {$evaluation->minimum_score} et {$evaluation->maximum_score}.");
            }
        }

        // Vérifier que l'élève est inscrit dans cette classe
        $student = Student::find($data['student_id']);

        if (!$student) {
            throw new Exception('Élève non trouvé.');
        }

        $isEnrolled = $student->enrollments()
            ->where('class_id', $evaluation->class_id)
            ->when($evaluation->academic_year_id, fn($q) => $q->where('academic_year_id', $evaluation->academic_year_id))
            ->exists();

        if (!$isEnrolled) {
            throw new Exception('L\'élève n\'est pas inscrit dans cette classe.');
        }
    }

    protected function canRecordGrade(int $evaluationId, int $studentId, $userId): bool
    {
        return $this->gradeRepository->canRecordGrade($evaluationId, $studentId, $userId);
    }

    protected function calculateClassPassingRate(Collection $grades): float
    {
        $presentGrades = $grades->where('is_absent', false);

        if ($presentGrades->count() === 0) {
            return 0;
        }

        $passingGrades = $presentGrades->where('weighted_score', '>=', 10);

        return round(($passingGrades->count() / $presentGrades->count()) * 100, 1);
    }
}
