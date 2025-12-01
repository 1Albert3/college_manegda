<?php

namespace Modules\Grade\Http\Controllers\Api;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use App\Http\Responses\ApiResponse;
use App\Http\Controllers\Controller;
use Modules\Grade\Services\EvaluationService;
use Modules\Grade\Entities\Evaluation;
use Exception;

/**
 * @group Grade Management
 * Gestion des évaluations et contrôles
 */
class EvaluationController extends Controller
{
    protected $evaluationService;

    public function __construct(EvaluationService $evaluationService)
    {
        $this->evaluationService = $evaluationService;
    }

    /**
     * Display a listing of evaluations.
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $filters = $request->only([
                'subject_id', 'class_id', 'teacher_id', 'academic_year_id',
                'type', 'period', 'status', 'date_from', 'date_to',
                'search', 'sort_by', 'sort_order', 'per_page'
            ]);

            $evaluations = app(\Modules\Grade\Repositories\EvaluationRepository::class)
                          ->search($filters, $request->get('per_page', 15));

            return ApiResponse::success('Évaluations récupérées avec succès', $evaluations);
        } catch (Exception $e) {
            Log::error('Failed to get evaluations list', [
                'error' => $e->getMessage(),
                'filters' => $filters ?? []
            ]);
            return ApiResponse::error('Erreur lors de la récupération des évaluations', 500);
        }
    }

    /**
     * Store a newly created evaluation.
     */
    public function store(Request $request): JsonResponse
    {
        try {
            $data = $request->validate([
                'name' => 'required|string|max:255',
                'code' => 'nullable|string|max:50',
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
            ]);

            $evaluation = $this->evaluationService->createEvaluation($data);

            return ApiResponse::success('Évaluation créée avec succès', $evaluation, 201);
        } catch (Exception $e) {
            Log::error('Failed to create evaluation', [
                'data' => $request->all(),
                'error' => $e->getMessage()
            ]);
            return ApiResponse::error($e->getMessage(), 400);
        }
    }

    /**
     * Display the specified evaluation.
     */
    public function show(Evaluation $evaluation): JsonResponse
    {
        try {
            $evaluation->load(['subject', 'class', 'teacher', 'academicYear']);

            return ApiResponse::success('Évaluation récupérée avec succès', $evaluation);
        } catch (Exception $e) {
            Log::error('Failed to get evaluation', [
                'evaluation_id' => $evaluation->id,
                'error' => $e->getMessage()
            ]);
            return ApiResponse::error('Erreur lors de la récupération de l\'évaluation', 500);
        }
    }

    /**
     * Update the specified evaluation.
     */
    public function update(Request $request, Evaluation $evaluation): JsonResponse
    {
        try {
            $data = $request->validate([
                'name' => 'sometimes|required|string|max:255',
                'code' => 'nullable|string|max:50',
                'description' => 'nullable|string',
                'type' => 'sometimes|required|in:continuous,semester,annual',
                'period' => 'sometimes|required|string|max:50',
                'coefficient' => 'sometimes|required|integer|min:1|max:10',
                'weight_percentage' => 'sometimes|required|decimal:0,2|min:0|max:100',
                'academic_year_id' => 'sometimes|required|exists:academic_years,id',
                'subject_id' => 'sometimes|required|exists:subjects,id',
                'class_id' => 'sometimes|required|exists:class_rooms,id',
                'teacher_id' => 'sometimes|required|exists:users,id',
                'evaluation_date' => 'sometimes|required|date|after_or_equal:today',
                'maximum_score' => 'sometimes|required|decimal:0,2|min:0|max:100',
                'minimum_score' => 'sometimes|required|decimal:0,2|min:0|lte:maximum_score',
                'grading_criteria' => 'nullable|array',
                'comments' => 'nullable|string',
            ]);

            $updatedEvaluation = $this->evaluationService->updateEvaluation($evaluation, $data);

            return ApiResponse::success('Évaluation mise à jour avec succès', $updatedEvaluation);
        } catch (Exception $e) {
            Log::error('Failed to update evaluation', [
                'evaluation_id' => $evaluation->id,
                'data' => $request->all(),
                'error' => $e->getMessage()
            ]);
            return ApiResponse::error($e->getMessage(), 400);
        }
    }

    /**
     * Remove the specified evaluation.
     */
    public function destroy(Evaluation $evaluation): JsonResponse
    {
        try {
            $deleted = $this->evaluationService->deleteEvaluation($evaluation);

            if ($deleted) {
                return ApiResponse::success('Évaluation supprimée avec succès');
            }

            return ApiResponse::error('Erreur lors de la suppression de l\'évaluation', 500);
        } catch (Exception $e) {
            Log::error('Failed to delete evaluation', [
                'evaluation_id' => $evaluation->id,
                'error' => $e->getMessage()
            ]);
            return ApiResponse::error($e->getMessage(), 400);
        }
    }

    /**
     * Get evaluation statistics.
     */
    public function getEvaluationReport(int $evaluationId): JsonResponse
    {
        try {
            $report = app(\Modules\Grade\Repositories\EvaluationRepository::class)
                     ->getEvaluationReport($evaluationId);

            return ApiResponse::success($report, 'Rapport d\'évaluation récupéré avec succès');
        } catch (Exception $e) {
            Log::error('Failed to get evaluation report', [
                'evaluation_id' => $evaluationId,
                'error' => $e->getMessage()
            ]);
            return ApiResponse::error('Erreur lors de la récupération du rapport', 500);
        }
    }

    /**
     * Generate evaluation result PDF.
     */
    public function generateResultPDF(int $evaluationId): JsonResponse
    {
        try {
            // Return PDF generation URL or file path
            $filename = 'resultats_evaluation_' . $evaluationId . '_' . date('Y-m-d') . '.pdf';

            return ApiResponse::success([
                'filename' => $filename,
                'url' => route('api.grade.evaluations.pdf', $evaluationId)
            ], 'PDF généré avec succès');
        } catch (Exception $e) {
            Log::error('Failed to generate evaluation PDF', [
                'evaluation_id' => $evaluationId,
                'error' => $e->getMessage()
            ]);
            return ApiResponse::error('Erreur lors de la génération du PDF', 500);
        }
    }

    /**
     * Download evaluation result PDF.
     */
    public function downloadResultPDF(int $evaluationId)
    {
        try {
            return $this->evaluationService->generateEvaluationResultPDF($evaluationId);
        } catch (Exception $e) {
            Log::error('Failed to download evaluation PDF', [
                'evaluation_id' => $evaluationId,
                'error' => $e->getMessage()
            ]);
            return ApiResponse::error('Erreur lors du téléchargement du PDF', 500);
        }
    }

    /**
     * Get evaluations by teacher.
     */
    public function getByTeacher(Request $request, int $teacherId): JsonResponse
    {
        try {
            $evaluations = app(\Modules\Grade\Repositories\EvaluationRepository::class)
                          ->getByTeacher($teacherId);

            return ApiResponse::success('Évaluations de l\'enseignant récupérées avec succès', $evaluations);
        } catch (Exception $e) {
            Log::error('Failed to get teacher evaluations', [
                'teacher_id' => $teacherId,
                'error' => $e->getMessage()
            ]);
            return ApiResponse::error('Erreur lors de la récupération des évaluations', 500);
        }
    }

    /**
     * Get evaluations by class.
     */
    public function getByClass(Request $request, int $classId): JsonResponse
    {
        try {
            $evaluations = app(\Modules\Grade\Repositories\EvaluationRepository::class)
                          ->getByClass($classId);

            $academicYearId = $request->get('academic_year_id');
            if ($academicYearId) {
                $evaluations = $evaluations->where('academic_year_id', $academicYearId);
            }

            return ApiResponse::success('Évaluations de la classe récupérées avec succès', $evaluations);
        } catch (Exception $e) {
            Log::error('Failed to get class evaluations', [
                'class_id' => $classId,
                'error' => $e->getMessage()
            ]);
            return ApiResponse::error('Erreur lors de la récupération des évaluations', 500);
        }
    }

    /**
     * Get evaluations by subject.
     */
    public function getBySubject(Request $request, int $subjectId): JsonResponse
    {
        try {
            $evaluations = app(\Modules\Grade\Repositories\EvaluationRepository::class)
                          ->getBySubject($subjectId);

            return ApiResponse::success('Évaluations de la matière récupérées avec succès', $evaluations);
        } catch (Exception $e) {
            Log::error('Failed to get subject evaluations', [
                'subject_id' => $subjectId,
                'error' => $e->getMessage()
            ]);
            return ApiResponse::error('Erreur lors de la récupération des évaluations', 500);
        }
    }

    /**
     * Get upcoming evaluations.
     */
    public function getUpcoming(): JsonResponse
    {
        try {
            $days = request('days', 7);
            $evaluations = app(\Modules\Grade\Repositories\EvaluationRepository::class)
                          ->getUpcoming($days);

            return ApiResponse::success('Évaluations à venir récupérées avec succès', $evaluations);
        } catch (Exception $e) {
            Log::error('Failed to get upcoming evaluations', [
                'error' => $e->getMessage()
            ]);
            return ApiResponse::error('Erreur lors de la récupération des évaluations à venir', 500);
        }
    }

    /**
     * Start evaluation.
     */
    public function start(int $evaluationId): JsonResponse
    {
        try {
            $evaluation = $this->evaluationService->startEvaluation($evaluationId);

            return ApiResponse::success('Évaluation démarrée avec succès', $evaluation);
        } catch (Exception $e) {
            Log::error('Failed to start evaluation', [
                'evaluation_id' => $evaluationId,
                'error' => $e->getMessage()
            ]);
            return ApiResponse::error($e->getMessage(), 400);
        }
    }

    /**
     * Complete evaluation.
     */
    public function complete(int $evaluationId): JsonResponse
    {
        try {
            $evaluation = $this->evaluationService->completeEvaluation($evaluationId);

            return ApiResponse::success('Évaluation terminée avec succès', $evaluation);
        } catch (Exception $e) {
            Log::error('Failed to complete evaluation', [
                'evaluation_id' => $evaluationId,
                'error' => $e->getMessage()
            ]);
            return ApiResponse::error($e->getMessage(), 400);
        }
    }

    /**
     * Cancel evaluation.
     */
    public function cancel(int $evaluationId): JsonResponse
    {
        try {
            $evaluation = Evaluation::findOrFail($evaluationId);
            $evaluation->cancel();

            return ApiResponse::success('Évaluation annulée avec succès', $evaluation);
        } catch (Exception $e) {
            Log::error('Failed to cancel evaluation', [
                'evaluation_id' => $evaluationId,
                'error' => $e->getMessage()
            ]);
            return ApiResponse::error($e->getMessage(), 400);
        }
    }

    /**
     * Bulk create evaluations.
     */
    public function bulkCreate(Request $request): JsonResponse
    {
        try {
            $data = $request->validate([
                'evaluations' => 'required|array|min:1',
                'evaluations.*.name' => 'required|string|max:255',
                'evaluations.*.type' => 'required|in:continuous,semester,annual',
                'evaluations.*.period' => 'required|string|max:50',
                'evaluations.*.coefficient' => 'required|integer|min:1|max:10',
                'evaluations.*.weight_percentage' => 'required|decimal:0,2|min:0|max:100',
                'evaluations.*.academic_year_id' => 'required|exists:academic_years,id',
                'evaluations.*.subject_id' => 'required|exists:subjects,id',
                'evaluations.*.class_id' => 'required|exists:class_rooms,id',
                'evaluations.*.teacher_id' => 'required|exists:users,id',
                'evaluations.*.evaluation_date' => 'required|date|after_or_equal:today',
                'evaluations.*.maximum_score' => 'required|decimal:0,2|min:0|max:100',
                'evaluations.*.minimum_score' => 'required|decimal:0,2|min:0|lte:evaluations.*.maximum_score',
            ]);

            $evaluations = $this->evaluationService->bulkCreateEvaluations($data['evaluations']);

            return ApiResponse::success('Évaluations créées en masse avec succès', $evaluations, 201);
        } catch (Exception $e) {
            Log::error('Failed to bulk create evaluations', [
                'data' => $request->all(),
                'error' => $e->getMessage()
            ]);
            return ApiResponse::error($e->getMessage(), 400);
        }
    }
}
