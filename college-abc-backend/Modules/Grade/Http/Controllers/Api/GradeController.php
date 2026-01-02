<?php

namespace Modules\Grade\Http\Controllers\Api;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use App\Http\Responses\ApiResponse;
use App\Http\Controllers\Controller;
use Modules\Grade\Services\GradeService;
use Modules\Grade\Services\ReportCardService;
use Modules\Grade\Entities\Grade;
use Exception;

/**
 * @group Grade Management
 * Gestion des notes individuelles
 */
class GradeController extends Controller
{
    use \Illuminate\Foundation\Auth\Access\AuthorizesRequests;

    protected $gradeService;
    protected $reportCardService;

    public function __construct(GradeService $gradeService, ReportCardService $reportCardService)
    {
        $this->gradeService = $gradeService;
        $this->reportCardService = $reportCardService;
    }

    /**
     * Display a listing of grades.
     */
    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Grade::class);

        try {
            $filters = $request->only([
                'student_id',
                'evaluation_id',
                'academic_year_id',
                'subject_id',
                'class_id',
                'is_absent',
                'grade_from',
                'grade_to',
                'recorded_from',
                'recorded_to',
                'search',
                'sort_by',
                'sort_order',
                'per_page'
            ]);

            $grades = app(\Modules\Grade\Repositories\GradeRepository::class)
                ->search($filters, $request->get('per_page', 15));

            return ApiResponse::success($grades, 'Notes récupérées avec succès');
        } catch (Exception $e) {
            Log::error('Failed to get grades list', [
                'error' => $e->getMessage(),
                'filters' => $filters ?? []
            ]);
            return ApiResponse::error('Erreur lors de la récupération des notes', 500);
        }
    }

    /**
     * Record a single grade.
     */
    public function record(Request $request): JsonResponse
    {
        $this->authorize('create', Grade::class);

        try {
            $data = $request->validate([
                'student_id' => 'required|exists:students,id',
                'evaluation_id' => 'required|exists:evaluations,id',
                'score' => 'nullable|decimal:0,2|min:0|max:20',
                'coefficient' => 'nullable|decimal:0,1|min:0.1|max:5',
                'is_absent' => 'nullable|boolean',
                'comments' => 'nullable|string|max:500',
            ]);

            // Security: Check if teacher owns the evaluation
            if ($request->user()->hasRole('teacher')) {
                $evaluation = \Modules\Grade\Entities\Evaluation::findOrFail($data['evaluation_id']);
                if ($evaluation->teacher_id !== $request->user()->id && !$request->user()->can('manage-grades')) {
                    abort(403, 'Vous ne pouvez saisir des notes que pour vos propres évaluations.');
                }
            }

            $userId = $request->user()->id;
            $grade = $this->gradeService->recordGrade($data, $userId);

            return ApiResponse::success($grade, 'Note enregistrée avec succès', 201);
        } catch (Exception $e) {
            Log::error('Failed to record grade', [
                'data' => $request->all(),
                'error' => $e->getMessage()
            ]);
            return ApiResponse::error($e->getMessage(), 400);
        }
    }

    /**
     * Bulk record grades.
     */
    public function bulkRecord(Request $request): JsonResponse
    {
        $this->authorize('create', Grade::class);

        try {
            $data = $request->validate([
                'evaluation_id' => 'required|exists:evaluations,id',
                'grades' => 'required|array|min:1',
                'grades.*.student_id' => 'required|exists:students,id',
                'grades.*.score' => 'nullable|numeric|min:0|max:20',
                'grades.*.coefficient' => 'nullable|numeric|min:0.1|max:5',
                'grades.*.is_absent' => 'nullable|boolean',
                'grades.*.comment' => 'nullable|string|max:500',
                'grades.*.comments' => 'nullable|string|max:500',
            ]);

            $userId = $request->user()->id;
            $evaluationId = $data['evaluation_id'];

            // Inject evaluation_id into each grade
            $gradesWithEvaluation = array_map(function ($grade) use ($evaluationId) {
                $grade['evaluation_id'] = $evaluationId;
                // Normalize comment/comments field
                if (isset($grade['comment']) && !isset($grade['comments'])) {
                    $grade['comments'] = $grade['comment'];
                }
                unset($grade['comment']);
                return $grade;
            }, $data['grades']);

            $grades = $this->gradeService->bulkRecordGrades($gradesWithEvaluation, $userId);

            return ApiResponse::success($grades, 'Notes enregistrées en masse avec succès', 201);
        } catch (\Illuminate\Validation\ValidationException $e) {
            Log::warning('Validation failed for bulk grade recording', [
                'errors' => $e->errors(),
                'input' => $request->all()
            ]);
            return ApiResponse::error('Validation échouée: ' . implode(', ', array_map(fn($msgs) => implode(', ', $msgs), $e->errors())), 422);
        } catch (Exception $e) {
            Log::error('Failed to bulk record grades', [
                'data' => $request->all(),
                'error' => $e->getMessage()
            ]);
            return ApiResponse::error($e->getMessage(), 400);
        }
    }

    /**
     * Display the specified grade.
     */
    public function show(int $id): JsonResponse
    {
        $grade = Grade::with(['student', 'evaluation.subject', 'evaluation.teacher', 'recorder'])
            ->findOrFail($id);

        $this->authorize('view', $grade);

        try {
            return ApiResponse::success($grade, 'Note récupérée avec succès');
        } catch (Exception $e) {
            Log::error('Failed to get grade', [
                'grade_id' => $grade->id,
                'error' => $e->getMessage()
            ]);
            return ApiResponse::error('Erreur lors de la récupération de la note', 500);
        }
    }

    /**
     * Update the specified grade.
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $grade = Grade::findOrFail($id);
        $this->authorize('update', $grade);

        try {
            $data = $request->validate([
                'score' => 'nullable|decimal:0,2|min:0|max:20',
                'coefficient' => 'nullable|decimal:0,1|min:0.1|max:5',
                'is_absent' => 'nullable|boolean',
                'comments' => 'nullable|string|max:500',
            ]);

            $updatedGrade = $this->gradeService->updateGrade($grade, $data);

            return ApiResponse::success($updatedGrade, 'Note mise à jour avec succès');
        } catch (Exception $e) {
            Log::error('Failed to update grade', [
                'grade_id' => $grade->id,
                'data' => $request->all(),
                'error' => $e->getMessage()
            ]);
            return ApiResponse::error($e->getMessage(), 400);
        }
    }

    /**
     * Remove the specified grade (soft delete).
     */
    public function destroy(int $id): JsonResponse
    {
        $grade = Grade::findOrFail($id);
        $this->authorize('delete', $grade);

        try {
            $grade->delete();

            return ApiResponse::success('Note supprimée avec succès');
        } catch (Exception $e) {
            Log::error('Failed to delete grade', [
                'grade_id' => $grade->id,
                'error' => $e->getMessage()
            ]);
            return ApiResponse::error('Erreur lors de la suppression de la note', 500);
        }
    }

    /**
     * Restore a soft deleted grade.
     */
    public function restore($gradeId): JsonResponse
    {
        // Restore usually requires admin privileges or manage-grades
        $this->authorize('create', Grade::class);

        try {
            $restored = app(\Modules\Grade\Repositories\GradeRepository::class)->restore($gradeId);

            if ($restored) {
                return ApiResponse::success('Note restaurée avec succès');
            }

            return ApiResponse::error('Note non trouvée ou erreur lors de la restauration', 404);
        } catch (Exception $e) {
            Log::error('Failed to restore grade', [
                'grade_id' => $gradeId,
                'error' => $e->getMessage()
            ]);
            return ApiResponse::error('Erreur lors de la restauration de la note', 500);
        }
    }

    /**
     * Force delete a grade (permanent deletion).
     */
    public function forceDelete($gradeId): JsonResponse
    {
        $this->authorize('forceDelete', Grade::class); // Needs policy method or generic check

        try {
            $deleted = app(\Modules\Grade\Repositories\GradeRepository::class)->forceDelete($gradeId);

            if ($deleted) {
                return ApiResponse::success('Note supprimée définitivement avec succès');
            }

            return ApiResponse::error('Note non trouvée ou erreur lors de la suppression', 404);
        } catch (Exception $e) {
            Log::error('Failed to force delete grade', [
                'grade_id' => $gradeId,
                'error' => $e->getMessage()
            ]);
            return ApiResponse::error('Erreur lors de la suppression définitive de la note', 500);
        }
    }

    /**
     * Get grades by student.
     */
    public function getByStudent(int $studentId, Request $request): JsonResponse
    {
        // Use student policy or check if user is parent/student
        $student = \Modules\Student\Entities\Student::findOrFail($studentId);
        // Authorization check logic reused from StudentPolicy usually, or manual check here:
        if ($request->user()->cant('view', $student) && $request->user()->cant('view-grades')) {
            abort(403);
        }

        try {
            $grades = app(\Modules\Grade\Repositories\GradeRepository::class)
                ->getByStudent($studentId);

            if ($request->has('academic_year_id')) {
                $grades = $grades->whereHas('evaluation', function ($q) use ($request) {
                    $q->where('academic_year_id', $request->academic_year_id);
                });
            }

            return ApiResponse::success($grades, 'Notes de l\'élève récupérées avec succès');
        } catch (Exception $e) {
            Log::error('Failed to get student grades', [
                'student_id' => $studentId,
                'error' => $e->getMessage()
            ]);
            return ApiResponse::error('Erreur lors de la récupération des notes', 500);
        }
    }

    /**
     * Get grades by evaluation.
     */
    public function getByEvaluation(int $evaluationId): JsonResponse
    {
        $evaluation = \Modules\Grade\Entities\Evaluation::findOrFail($evaluationId);
        $this->authorize('view', $evaluation); // Use EvaluationPolicy

        try {
            $grades = app(\Modules\Grade\Repositories\GradeRepository::class)
                ->getClassGradesForEvaluation($evaluationId);

            return ApiResponse::success($grades, 'Notes de l\'évaluation récupérées avec succès');
        } catch (Exception $e) {
            Log::error('Failed to get evaluation grades', [
                'evaluation_id' => $evaluationId,
                'error' => $e->getMessage()
            ]);
            return ApiResponse::error('Erreur lors de la récupération des notes', 500);
        }
    }

    /**
     * Get student grades report.
     */
    public function getStudentReport(int $studentId, Request $request): JsonResponse
    {
        $student = \Modules\Student\Entities\Student::findOrFail($studentId);
        if ($request->user()->cant('view', $student) && $request->user()->cant('view-grades')) {
            abort(403);
        }

        try {
            $academicYearId = $request->get('academic_year_id');

            $report = $this->reportCardService->calculateStudentAverages($studentId, $academicYearId);

            return ApiResponse::success($report, 'Rapport des notes de l\'élève récupéré avec succès');
        } catch (Exception $e) {
            Log::error('Failed to get student grades report', [
                'student_id' => $studentId,
                'error' => $e->getMessage()
            ]);
            return ApiResponse::error('Erreur lors de la récupération du rapport', 500);
        }
    }

    /**
     * Get class grades report with ranking (Bulletin de classe).
     */
    public function getClassReport(int $classId, Request $request): JsonResponse
    {
        $this->authorize('viewAny', Grade::class);

        try {
            $academicYearId = $request->get('academic_year_id');
            $periodId = $request->get('period_id');

            if (!$academicYearId) {
                return ApiResponse::error('L\'année académique est requise', 400);
            }

            $report = $this->reportCardService->generateClassReport($classId, $academicYearId, $periodId);

            return ApiResponse::success($report, 'Classement de la classe récupéré avec succès');
        } catch (Exception $e) {
            Log::error('Failed to get class grades report', [
                'class_id' => $classId,
                'error' => $e->getMessage()
            ]);
            return ApiResponse::error('Erreur lors de la récupération du rapport', 500);
        }
    }

    /**
     * Get teacher grades report.
     */
    public function getTeacherReport(int $teacherId, Request $request): JsonResponse
    {
        $this->authorize('viewAny', Grade::class);
        // Teacher can see own report
        if ($request->user()->hasRole('teacher') && $request->user()->id !== $teacherId) {
            abort(403);
        }

        try {
            $academicYearId = $request->get('academic_year_id');
            $report = $this->gradeService->getTeacherGradesReport($teacherId, $academicYearId);

            return ApiResponse::success($report, 'Rapport des notes de l\'enseignant récupéré avec succès');
        } catch (Exception $e) {
            Log::error('Failed to get teacher grades report', [
                'teacher_id' => $teacherId,
                'error' => $e->getMessage()
            ]);
            return ApiResponse::error('Erreur lors de la récupération du rapport', 500);
        }
    }

    /**
     * Generate student report card PDF.
     */
    public function generateReportCardPDF(int $studentId, Request $request): JsonResponse
    {
        $student = \Modules\Student\Entities\Student::findOrFail($studentId);
        // Authorization...
        if ($request->user()->cant('view', $student) && $request->user()->cant('view-grades')) {
            abort(403);
        }

        try {
            $academicYearId = $request->get('academic_year_id');

            return ApiResponse::success([
                'student_id' => $studentId,
                'academic_year_id' => $academicYearId,
                'url' => route('api.grade.grades.report-card-pdf', [
                    'studentId' => $studentId,
                    'academicYearId' => $academicYearId
                ])
            ], 'Génération du bulletin en cours');
        } catch (Exception $e) {
            Log::error('Failed to generate report card PDF', [
                'student_id' => $studentId,
                'academic_year_id' => $academicYearId ?? null,
                'error' => $e->getMessage()
            ]);
            return ApiResponse::error('Erreur lors de la génération du bulletin', 500);
        }
    }

    /**
     * Download student report card PDF.
     */
    public function downloadReportCardPDF(Request $request, int $studentId, int $academicYearId)
    {
        // Auth check... might come from query link, need token in header usually or specific signed URL logic.
        // Assuming API auth middleware is active.
        $student = \Modules\Student\Entities\Student::findOrFail($studentId);
        if ($request->user()->cant('view', $student) && $request->user()->cant('view-grades')) {
            abort(403);
        }

        try {
            return app(\Modules\Grade\Services\EvaluationService::class)
                ->generateReportCardPDF($studentId, $academicYearId);
        } catch (Exception $e) {
            Log::error('Failed to download report card PDF', [
                'student_id' => $studentId,
                'academic_year_id' => $academicYearId,
                'error' => $e->getMessage()
            ]);
            return ApiResponse::error('Erreur lors du téléchargement du bulletin', 500);
        }
    }

    /**
     * Generate class grades PDF.
     */
    public function generateClassGradesPDF(int $classId, Request $request)
    {
        // Only teachers associated or admins
        $this->authorize('viewAny', Grade::class);

        try {
            $academicYearId = $request->get('academic_year_id');
            return app(\Modules\Grade\Services\EvaluationService::class)
                ->generateClassGradesPDF($classId, $academicYearId);
        } catch (Exception $e) {
            Log::error('Failed to generate class grades PDF', [
                'class_id' => $classId,
                'error' => $e->getMessage()
            ]);
            return ApiResponse::error('Erreur lors de la génération du PDF de la classe', 500);
        }
    }

    /**
     * Get absent grades.
     */
    public function getAbsent(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Grade::class);

        try {
            $filters = $request->only(['evaluation_id', 'class_id']);
            $grades = app(\Modules\Grade\Repositories\GradeRepository::class)
                ->getAbsentGrades($filters);

            return ApiResponse::success('Notes d\'absence récupérées avec succès', $grades);
        } catch (Exception $e) {
            Log::error('Failed to get absent grades', [
                'error' => $e->getMessage()
            ]);
            return ApiResponse::error('Erreur lors de la récupération des notes d\'absence', 500);
        }
    }

    /**
     * Get grade statistics.
     */
    public function getStatistics(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Grade::class);

        try {
            $academicYearId = $request->get('academic_year_id');
            $stats = app(\Modules\Grade\Services\EvaluationService::class)
                ->getAcademicYearStats($academicYearId);

            return ApiResponse::success($stats, 'Statistiques récupérées avec succès');
        } catch (Exception $e) {
            Log::error('Failed to get grade statistics', [
                'error' => $e->getMessage()
            ]);
            return ApiResponse::error('Erreur lors de la récupération des statistiques', 500);
        }
    }

    /**
     * Get school-wide statistics.
     */
    public function getSchoolStats(): JsonResponse
    {
        $this->authorize('viewAny', Grade::class);

        try {
            $stats = app(\Modules\Grade\Services\EvaluationService::class)->getSchoolStats();

            return ApiResponse::success($stats, 'Statistiques scolaires récupérées avec succès');
        } catch (Exception $e) {
            Log::error('Failed to get school statistics', [
                'error' => $e->getMessage()
            ]);
            return ApiResponse::error('Erreur lors de la récupération des statistiques scolaires', 500);
        }
    }
}
