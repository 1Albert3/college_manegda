<?php

namespace Modules\Academic\Http\Controllers\Api;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Academic\Services\SubjectService;
use App\Http\Responses\ApiResponse;

/**
 * @group Academic Management
 * Gestion des matières scolaires
 */
class SubjectController extends Controller
{
    public function __construct(
        private SubjectService $subjectService
    ) {
        $this->middleware('permission:view-academic')->only(['index', 'show', 'byCategory', 'byLevel', 'grouped', 'stats', 'findByCode']);
        $this->middleware('permission:manage-academic')->except(['index', 'show', 'byCategory', 'byLevel', 'grouped', 'stats', 'findByCode']);
    }

    public function index(Request $request): JsonResponse
    {
        $filters = $request->only(['category', 'level_type', 'is_active', 'search', 'per_page']);

        $subjects = $this->subjectService->getSubjects($filters);

        return ApiResponse::paginated($subjects);
    }

    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'nullable|string|max:10|unique:subjects,code',
            'description' => 'nullable|string|max:1000',
            'category' => 'required|in:sciences,literature,language,social_studies,arts,physical_education,technology,other',
            'level_type' => 'required|in:primary,secondary,both',
            'coefficients' => 'nullable|numeric|min:0',
            'is_active' => 'boolean',
        ]);

        // Validation personnalisée
        $validationErrors = $this->subjectService->validateSubjectData($request->all());
        if (!empty($validationErrors)) {
            return ApiResponse::error('Erreurs de validation: ' . implode(', ', $validationErrors), 422);
        }

        $subject = $this->subjectService->createSubject($request->all());

        return ApiResponse::success($subject, 'Matière créée avec succès', 201);
    }

    public function show(int $id): JsonResponse
    {
        $subject = $this->subjectService->findSubject($id);

        return ApiResponse::success($subject->load(['classes', 'teachers', 'gradeComponents']));
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'nullable|string|max:10|unique:subjects,code,' . $id,
            'description' => 'nullable|string|max:1000',
            'category' => 'required|in:sciences,literature,language,social_studies,arts,physical_education,technology,other',
            'level_type' => 'required|in:primary,secondary,both',
            'coefficients' => 'nullable|numeric|min:0',
            'is_active' => 'boolean',
        ]);

        $subject = $this->subjectService->updateSubject($id, $request->all());

        return ApiResponse::success($subject, 'Matière mise à jour avec succès');
    }

    public function destroy(int $id): JsonResponse
    {
        $subject = $this->subjectService->findSubject($id);

        // Vérifier qu'on peut supprimer (pas assignée à des classes/parce qu'elle a des notes)
        if ($subject->classes()->exists() || $subject->gradeComponents()->exists()) {
            return ApiResponse::error('Impossible de supprimer une matière assignée à des classes ou contenant des notes.', 422);
        }

        $this->subjectService->deleteSubject($id);

        return ApiResponse::success(null, 'Matière supprimée avec succès', 204);
    }

    public function byCategory(string $category): JsonResponse
    {
        $subjects = $this->subjectService->getSubjectsByCategory($category);

        return ApiResponse::success($subjects);
    }

    public function byLevel(string $level): JsonResponse
    {
        $subjects = $this->subjectService->getSubjectsByLevelType($level);

        return ApiResponse::success($subjects);
    }

    public function grouped(Request $request): JsonResponse
    {
        $grouped = $this->subjectService->getSubjectsGroupedByCategory();

        return ApiResponse::success($grouped);
    }

    public function stats(Request $request): JsonResponse
    {
        $stats = $this->subjectService->getSubjectsStats();

        return ApiResponse::success($stats);
    }

    public function bulkActivate(Request $request): JsonResponse
    {
        $request->validate([
            'subject_ids' => 'required|array|min:1',
            'subject_ids.*' => 'integer|exists:subjects,id',
        ]);

        $updated = $this->subjectService->bulkActivate($request->subject_ids);

        return ApiResponse::success(['updated' => $updated], "$updated matière(s) activée(s)");
    }

    public function bulkDeactivate(Request $request): JsonResponse
    {
        $request->validate([
            'subject_ids' => 'required|array|min:1',
            'subject_ids.*' => 'integer|exists:subjects,id',
        ]);

        $updated = $this->subjectService->bulkDeactivate($request->subject_ids);

        return ApiResponse::success(['updated' => $updated], "$updated matière(s) désactivée(s)");
    }

    public function updateCoefficients(Request $request): JsonResponse
    {
        $request->validate([
            'coefficients' => 'required|array',
            'coefficients.*' => 'numeric|min:0',
        ]);

        $updated = $this->subjectService->updateCoefficients($request->coefficients);

        return ApiResponse::success(['updated' => $updated], "Coefficients mis à jour pour $updated matière(s)");
    }

    public function assignToClass(Request $request, int $id): JsonResponse
    {
        $request->validate([
            'class_id' => 'required|integer|exists:classes,id',
            'attributes' => 'nullable|array',
        ]);

        $this->subjectService->assignToClass($id, $request->class_id, $request->attributes ?? []);

        return ApiResponse::success(null, 'Matière assignée à la classe avec succès');
    }

    public function removeFromClass(Request $request, int $id): JsonResponse
    {
        $request->validate([
            'class_id' => 'required|integer|exists:classes,id',
        ]);

        $this->subjectService->removeFromClass($id, $request->class_id);

        return ApiResponse::success(null, 'Matière retirée de la classe avec succès');
    }

    public function assignTeacher(Request $request, int $id): JsonResponse
    {
        $request->validate([
            'teacher_id' => 'required|integer|exists:users,id',
            'academic_year_id' => 'nullable|integer|exists:academic_years,id',
        ]);

        $subject = $this->subjectService->assignTeacher(
            $id,
            $request->teacher_id,
            $request->academic_year_id
        );

        return ApiResponse::success($subject, 'Enseignant assigné à la matière avec succès');
    }

    public function getStudents(Request $request, int $id): JsonResponse
    {
        $subject = $this->subjectService->findSubject($id);

        $students = $subject->getEnrolledStudents();

        return ApiResponse::success($students);
    }

    public function getAverageGrade(Request $request, int $id, int $classId): JsonResponse
    {
        $subject = $this->subjectService->findSubject($id);

        $average = $subject->getAverageGradeForClass($classId);

        return ApiResponse::success(['average_grade' => $average], 'Moyenne calculée avec succès');
    }

    public function findByCode(string $code): JsonResponse
    {
        $subject = $this->subjectService->findByCode($code);

        return ApiResponse::success($subject->load(['classes', 'teachers']));
    }
}
