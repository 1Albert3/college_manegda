<?php

namespace Modules\Grade\Services;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Barryvdh\DomPDF\Facade\Pdf;
use Modules\Grade\Entities\Evaluation;
use Modules\Grade\Entities\Grade;
use Modules\Grade\Repositories\EvaluationRepository;
use Modules\Grade\Repositories\GradeRepository;
use Modules\Academic\Entities\AcademicYear;
use Modules\Academic\Entities\ClassRoom;
use Modules\Academic\Entities\Subject;
use Modules\Core\Entities\User;  // Assuming this is the correct namespace
use Exception;

class EvaluationService
{
    protected $evaluationRepository;
    protected $gradeRepository;
    protected $gradeService;

    public function __construct(
        EvaluationRepository $evaluationRepository,
        GradeRepository $gradeRepository,
        GradeService $gradeService
    ) {
        $this->evaluationRepository = $evaluationRepository;
        $this->gradeRepository = $gradeRepository;
        $this->gradeService = $gradeService;
    }

    // CRUD operations
    public function createEvaluation(array $data): Evaluation
    {
        try {
            $this->validateEvaluationData($data);
            $this->validateTeacherAssignment($data);

            // Générer le code automatiquement si non fourni
            if (!isset($data['code'])) {
                $data['code'] = $this->generateEvaluationCode($data);
            }

            DB::beginTransaction();

            $evaluation = $this->evaluationRepository->create($data);

            Log::info('Evaluation created', [
                'evaluation_id' => $evaluation->id,
                'code' => $evaluation->code,
                'teacher_id' => $evaluation->teacher_id,
                'subject_id' => $evaluation->subject_id,
                'class_id' => $evaluation->class_id,
            ]);

            DB::commit();
            return $evaluation;
        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Failed to create evaluation', [
                'data' => $data,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    public function updateEvaluation(Evaluation $evaluation, array $data): Evaluation
    {
        try {
            $this->validateEvaluationData($data, $evaluation->id);

            DB::beginTransaction();

            $updated = $this->evaluationRepository->update($evaluation, $data);

            if ($updated) {
                Log::info('Evaluation updated', [
                    'evaluation_id' => $evaluation->id,
                    'changes' => $data
                ]);
            }

            DB::commit();
            return $evaluation->fresh();
        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Failed to update evaluation', [
                'evaluation_id' => $evaluation->id,
                'data' => $data,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    public function deleteEvaluation(Evaluation $evaluation): bool
    {
        try {
            // Vérifier s'il y a des notes enregistrées
            if ($evaluation->grades()->exists()) {
                throw new Exception('Impossible de supprimer une évaluation qui contient des notes.');
            }

            DB::beginTransaction();

            $deleted = $this->evaluationRepository->delete($evaluation);

            if ($deleted) {
                Log::info('Evaluation deleted', [
                    'evaluation_id' => $evaluation->id,
                    'code' => $evaluation->code
                ]);
            }

            DB::commit();
            return $deleted;
        } catch (Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    public function startEvaluation(int $evaluationId): Evaluation
    {
        try {
            $evaluation = $this->evaluationRepository->findById($evaluationId);

            if (!$evaluation) {
                throw new Exception('Évaluation non trouvée.');
            }

            if ($evaluation->status === 'ongoing') {
                throw new Exception('L\'évaluation est déjà en cours.');
            }

            if ($evaluation->status === 'completed') {
                throw new Exception('L\'évaluation est déjà terminée.');
            }

            if ($evaluation->status === 'cancelled') {
                throw new Exception('L\'évaluation est annulée.');
            }

            DB::beginTransaction();

            $evaluation->start();

            Log::info('Evaluation started', [
                'evaluation_id' => $evaluation->id,
                'code' => $evaluation->code
            ]);

            DB::commit();
            return $evaluation;
        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Failed to start evaluation', [
                'evaluation_id' => $evaluationId,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    public function completeEvaluation(int $evaluationId): Evaluation
    {
        try {
            $evaluation = $this->evaluationRepository->findById($evaluationId);

            if (!$evaluation) {
                throw new Exception('Évaluation non trouvée.');
            }

            if ($evaluation->status !== 'ongoing') {
                throw new Exception('L\'évaluation doit être en cours pour être terminée.');
            }

            DB::beginTransaction();

            $evaluation->complete();

            Log::info('Evaluation completed', [
                'evaluation_id' => $evaluation->id,
                'code' => $evaluation->code
            ]);

            DB::commit();
            return $evaluation;
        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Failed to complete evaluation', [
                'evaluation_id' => $evaluationId,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    // Bulk operations
    public function bulkCreateEvaluations(array $evaluationsData): Collection
    {
        DB::beginTransaction();
        try {
            $evaluations = $this->evaluationRepository->bulkCreate($evaluationsData);
            DB::commit();

            Log::info('Bulk evaluations created', [
                'count' => $evaluations->count()
            ]);

            return $evaluations;
        } catch (Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    // PDF Generation
    public function generateReportCardPDF(int $studentId, int $academicYearId = null): string
    {
        try {
            $data = $this->gradeService->getStudentGradesReport($studentId, $academicYearId);

            $pdf = Pdf::loadView('grade::reports.report_card', $data)
                     ->setPaper('a4', 'portrait');

            $filename = 'bulletin_' . $data['student']->matricule . '_' .
                       ($academicYearId ? AcademicYear::find($academicYearId)->name : 'complet') . '.pdf';

            return $pdf->download($filename);
        } catch (Exception $e) {
            Log::error('Failed to generate report card PDF', [
                'student_id' => $studentId,
                'academic_year_id' => $academicYearId,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    public function generateClassGradesPDF(int $classId, int $academicYearId = null): string
    {
        try {
            $data = $this->gradeService->getClassGradesReport($classId, $academicYearId);

            $pdf = Pdf::loadView('grade::reports.class_grades', $data)
                     ->setPaper('a4', 'landscape');

            $class = $data['class'];
            $academicYear = $academicYearId ? AcademicYear::find($academicYearId) : null;

            $filename = 'notes_classe_' . $class->name . '_' .
                       ($academicYear ? $academicYear->name : 'complet') . '.pdf';

            return $pdf->download($filename);
        } catch (Exception $e) {
            Log::error('Failed to generate class grades PDF', [
                'class_id' => $classId,
                'academic_year_id' => $academicYearId,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    public function generateEvaluationResultPDF(int $evaluationId): string
    {
        try {
            $report = $this->evaluationRepository->getEvaluationReport($evaluationId);

            $pdf = Pdf::loadView('grade::reports.evaluation_result', $report)
                     ->setPaper('a4', 'portrait');

            $evaluation = $report['evaluation'];
            $filename = 'resultats_' . $evaluation->code . '_'. date('Y-m-d') . '.pdf';

            return $pdf->download($filename);
        } catch (Exception $e) {
            Log::error('Failed to generate evaluation result PDF', [
                'evaluation_id' => $evaluationId,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    // Analytics and Statistics
    public function getAcademicYearStats(int $academicYearId): array
    {
        try {
            $academicYear = AcademicYear::find($academicYearId);

            if (!$academicYear) {
                throw new Exception('Année académique non trouvée.');
            }

            $evaluations = $this->evaluationRepository->getByAcademicYear($academicYearId);
            $grades = Grade::whereHas('evaluation', function ($q) use ($academicYearId) {
                $q->where('academic_year_id', $academicYearId);
            })->get();

            $stats = [
                'academic_year' => $academicYear,
                'total_evaluations' => $evaluations->count(),
                'completed_evaluations' => $evaluations->where('status', 'completed')->count(),
                'ongoing_evaluations' => $evaluations->where('status', 'ongoing')->count(),
                'total_grades' => $grades->count(),
                'present_grades' => $grades->where('is_absent', false)->count(),
                'absent_grades' => $grades->where('is_absent', true)->count(),
                'average_score' => $grades->where('is_absent', false)->avg('weighted_score') ?? 0,
                'passing_rate' => $this->calculatePassingRate($grades),
                'grade_distribution' => $this->calculateGradeDistribution($grades),
            ];

            // Statistiques par période
            $periodStats = [];
            foreach (['Trimestre 1', 'Trimestre 2', 'Trimestre 3', 'Année'] as $period) {
                $periodEvaluations = $evaluations->where('period', $period);
                if ($periodEvaluations->count() > 0) {
                    $periodGrades = collect();
                    foreach ($periodEvaluations as $evaluation) {
                        $evaluationGrades = $this->gradeRepository->getByEvaluation($evaluation->id);
                        $periodGrades = $periodGrades->merge($evaluationGrades);
                    }

                    $periodStats[$period] = [
                        'evaluations_count' => $periodEvaluations->count(),
                        'grades_count' => $periodGrades->count(),
                        'average' => $periodGrades->where('is_absent', false)->avg('weighted_score') ?? 0,
                        'passing_rate' => $this->calculatePassingRate($periodGrades),
                    ];
                }
            }

            $stats['period_stats'] = $periodStats;

            return $stats;
        } catch (Exception $e) {
            Log::error('Failed to get academic year stats', [
                'academic_year_id' => $academicYearId,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    public function getSchoolStats(): array
    {
        try {
            $currentYear = AcademicYear::current()->first();

            $evaluations = $this->evaluationRepository->getActive();
            $grades = $currentYear ? Grade::whereHas('evaluation', function ($q) use ($currentYear) {
                $q->where('academic_year_id', $currentYear->id);
            })->get() : collect();

            return [
                'current_academic_year' => $currentYear,
                'active_evaluations' => $evaluations->count(),
                'total_grades_recorded' => $grades->count(),
                'overall_average' => $grades->where('is_absent', false)->avg('weighted_score') ?? 0,
                'overall_passing_rate' => $this->calculatePassingRate($grades),
                'today_evaluations' => $evaluations->where('evaluation_date', today())->count(),
                'upcoming_evaluations' => $evaluations->where('evaluation_date', '>', today())
                                              ->where('evaluation_date', '<=', today()->addDays(7))->count(),
                'completion_stats' => $this->evaluationRepository->getCompletionStats(),
            ];
        } catch (Exception $e) {
            Log::error('Failed to get school stats', [
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    // Utility methods
    protected function validateEvaluationData(array $data, int $excludeId = null): void
    {
        $rules = [
            'name' => 'required|string|max:255',
            'code' => 'nullable|string|max:50|unique:evaluations,code' . ($excludeId ? ',' . $excludeId : ''),
            'description' => 'nullable|string',
            'type' => 'required|in:continuous,semester,annual',
            'period' => 'required|string|max:50',
            'coefficient' => 'required|integer|min:1|max:10',
            'weight_percentage' => 'required|decimal:0,2|min:0|max:100',
            'academic_year_id' => 'required|exists:academic_years,id',
            'subject_id' => 'required|exists:subjects,id',
            'class_id' => 'required|exists:class_rooms,id',
            'teacher_id' => 'required|exists:users,id',
            'evaluation_date' => 'required|date|after_or_equal:today',
            'maximum_score' => 'required|decimal:0,2|min:0|max:100',
            'minimum_score' => 'required|decimal:0,2|min:0|lte:maximum_score',
            'grading_criteria' => 'nullable|array',
            'comments' => 'nullable|string',
        ];

        $validator = validator($data, $rules);

        if ($validator->fails()) {
            throw new Exception('Données invalides: ' . implode(', ', $validator->errors()->all()));
        }

        // Validation métier
        $hasConflict = $this->evaluationRepository->hasConflictingSchedule(
            $data['teacher_id'],
            $data['evaluation_date'],
            $excludeId
        );

        if ($hasConflict) {
            throw new Exception('L\'enseignant a déjà une évaluation programmée ce jour-là.');
        }
    }

    protected function validateTeacherAssignment(array $data): void
    {
        $isAssigned = $this->evaluationRepository->isTeacherAssignedToSubjectAndClass(
            $data['teacher_id'],
            $data['subject_id'],
            $data['class_id'],
            $data['academic_year_id']
        );

        if (!$isAssigned) {
            throw new Exception('L\'enseignant n\'est pas assigné à cette matière dans cette classe.');
        }
    }

    protected function generateEvaluationCode(array $data): string
    {
        $subject = Subject::find($data['subject_id']);
        $teacher = User::find($data['teacher_id']);
        $academicYear = AcademicYear::find($data['academic_year_id']);

        $prefix = strtoupper(substr($subject->name, 0, 3));
        $yearLastDigits = substr($academicYear->name, -2);
        $timestamp = now()->format('md');

        return $prefix . $yearLastDigits . $timestamp;
    }

    protected function calculatePassingRate(Collection $grades): float
    {
        $presentGrades = $grades->where('is_absent', false);
        $passingGrades = $presentGrades->where('weighted_score', '>=', 10);

        return $presentGrades->count() > 0
            ? round(($passingGrades->count() / $presentGrades->count()) * 100, 1)
            : 0;
    }

    protected function calculateGradeDistribution(Collection $grades): array
    {
        $presentGrades = $grades->where('is_absent', false);
        $distribution = ['A+' => 0, 'A' => 0, 'B+' => 0, 'B' => 0, 'C+' => 0, 'C' => 0, 'D+' => 0, 'D' => 0, 'F' => 0];

        foreach ($presentGrades as $grade) {
            if (isset($distribution[$grade->grade_letter])) {
                $distribution[$grade->grade_letter]++;
            }
        }

        return $distribution;
    }
}
