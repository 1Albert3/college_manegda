<?php

namespace Modules\Academic\Http\Controllers\Api;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Academic\Services\AcademicYearService;
use App\Http\Responses\ApiResponse;
use Carbon\Carbon;

/**
 * @group Academic Management
 * Gestion des années académiques
 */
class AcademicYearController extends Controller
{
    public function __construct(
        private AcademicYearService $academicYearService
    ) {
        $this->middleware('permission:view-academic')->only(['index', 'show', 'current']);
        $this->middleware('permission:manage-academic')->except(['index', 'show', 'current']);
    }

    public function index(Request $request): JsonResponse
    {
        $filters = $request->only(['status', 'is_current', 'search']);

        $years = $this->academicYearService->getAcademicYears($filters);

        return ApiResponse::paginated($years);
    }

    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'name' => 'nullable|string|max:255',
            'start_date' => 'required|date|before:end_date',
            'end_date' => 'required|date|after:start_date',
            'status' => 'in:planned,active,completed,cancelled',
            'is_current' => 'boolean',
            'description' => 'nullable|string|max:1000',
        ]);

        // Validation: une année scolaire en cours = max
        if ($request->is_current && $this->academicYearService->getCurrentYear()) {
            return ApiResponse::error('Une année scolaire est déjà marquée comme actuelle.', 422);
        }

        $year = $this->academicYearService->createAcademicYear($request->all());

        return ApiResponse::success($year, 'Année académique créée avec succès', 201);
    }

    public function show(int $id): JsonResponse
    {
        $year = $this->academicYearService->findAcademicYear($id);

        return ApiResponse::success($year->load(['enrollments.student', 'teachers', 'subjects']));
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $request->validate([
            'name' => 'nullable|string|max:255',
            'start_date' => 'nullable|date|before:end_date',
            'end_date' => 'nullable|date|after:start_date',
            'status' => 'in:planned,active,completed,cancelled',
            'is_current' => 'boolean',
            'description' => 'nullable|string|max:1000',
            'semesters' => 'nullable|array',
        ]);

        $year = $this->academicYearService->updateAcademicYear($id, $request->all());

        return ApiResponse::success($year, 'Année académique mise à jour avec succès');
    }

    public function destroy(int $id): JsonResponse
    {
        $year = $this->academicYearService->findAcademicYear($id);

        // Vérifier qu'on peut supprimer
        if ($year->status === 'active' || $year->enrollments()->exists()) {
            return ApiResponse::error('Impossible de supprimer une année académique active avec des inscriptions.', 422);
        }

        // Soft delete ou hard delete selon politique
        $year->delete();

        return ApiResponse::success(null, 'Année académique supprimée avec succès', 204);
    }

    public function current(Request $request): JsonResponse
    {
        $year = $this->academicYearService->getCurrentYear();

        if (!$year) {
            return ApiResponse::error('Aucune année académique actuelle configurée', 404);
        }

        return ApiResponse::success($year->load(['enrollments.student', 'teachers', 'subjects']));
    }

    public function setCurrent(Request $request, int $id): JsonResponse
    {
        $request->validate([
            'force' => 'sometimes|boolean', // Force le changement même s'il y a des donnés
        ]);

        try {
            $year = $this->academicYearService->setCurrentYear($id);

            return ApiResponse::success($year, 'Année académique actuelle mise à jour avec succès');
        } catch (\Exception $e) {
            return ApiResponse::error($e->getMessage(), 422);
        }
    }

    public function complete(Request $request, int $id): JsonResponse
    {
        try {
            $year = $this->academicYearService->completeYear($id);

            return ApiResponse::success($year, 'Année académique marquée comme terminée');
        } catch (\Exception $e) {
            return ApiResponse::error($e->getMessage(), 422);
        }
    }

    public function stats(Request $request): JsonResponse
    {
        $stats = $this->academicYearService->getYearsStats();

        return ApiResponse::success($stats);
    }

    public function generateSemesters(Request $request, int $id): JsonResponse
    {
        $year = $this->academicYearService->findAcademicYear($id);

        $semesters = $this->academicYearService->generateSemesters(
            $year->start_date,
            $year->end_date
        );

        return ApiResponse::success($semesters, 'Semestres générés avec succès');
    }

    public function next(Request $request): JsonResponse
    {
        $nextYear = $this->academicYearService->getNextAcademicYear();

        if (!$nextYear) {
            return ApiResponse::error('Aucune année académique prochaine planifiée', 404);
        }

        return ApiResponse::success($nextYear);
    }

    public function previous(Request $request): JsonResponse
    {
        $previousYear = $this->academicYearService->getPreviousAcademicYear();

        if (!$previousYear) {
            return ApiResponse::error('Aucune année académique précédente trouvée', 404);
        }

        return ApiResponse::success($previousYear);
    }

    public function createFromTemplate(Request $request, int $id): JsonResponse
    {
        $request->validate([
            'start_date' => 'required|date',
            'end_date' => 'required|date|after:start_date',
            'name' => 'nullable|string|max:255',
        ]);

        $templateYear = $this->academicYearService->findAcademicYear($id);

        $newYearData = [
            'start_date' => Carbon::parse($request->start_date),
            'end_date' => Carbon::parse($request->end_date),
            'name' => $request->name,
            'description' => "Année créée à partir du modèle {$templateYear->name}",
            'status' => 'planned',
            'is_current' => false,
        ];

        $newYear = $this->academicYearService->createAcademicYear($newYearData);

        // Copier les matières si demandé
        if ($request->boolean('copy_subjects')) {
            // TODO: Implémenter la copie des matières
            // $this->copySubjectsFromYear($templateYear, $newYear);
        }

        // Copier les enseignants si demandé
        if ($request->boolean('copy_teachers')) {
            // TODO: Implémenter la copie des enseignants
            // $this->copyTeachersFromYear($templateYear, $newYear);
        }

        return ApiResponse::success($newYear, 'Année académique créée à partir du modèle', 201);
    }
}
