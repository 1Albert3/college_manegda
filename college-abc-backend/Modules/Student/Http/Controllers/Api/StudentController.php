<?php

namespace Modules\Student\Http\Controllers\Api;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Student\Services\StudentService;
use App\Http\Responses\ApiResponse;
use Spatie\QueryBuilder\QueryBuilder;

/**
 * @group Student Management
 * Gestion des élèves
 */
class StudentController extends Controller
{
    use \Illuminate\Foundation\Auth\Access\AuthorizesRequests;

    public function __construct(
        private StudentService $studentService
    ) {}

    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', \Modules\Student\Entities\Student::class);

        $students = QueryBuilder::for(\Modules\Student\Entities\Student::class)
            ->allowedFilters(['status', 'gender', 'matricule'])
            ->allowedIncludes(['user', 'parents', 'currentEnrollment.classRoom'])
            ->allowedSorts(['created_at', 'first_name', 'last_name', 'matricule'])
            ->with(['user', 'parents', 'currentEnrollment.classRoom'])
            ->paginate($request->get('per_page', 15));

        return ApiResponse::paginated($students);
    }

    public function store(Request $request): JsonResponse
    {
        $this->authorize('create', \Modules\Student\Entities\Student::class);

        $request->validate([
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'date_of_birth' => 'required|date|before:today',
            'gender' => 'required|in:M,F',
            'email' => 'nullable|email|unique:users,email',
            'phone' => 'nullable|string|regex:/^(\+?[0-9\s\-\(\)]*)$/|max:20',
            'place_of_birth' => 'nullable|string|max:255',
            'address' => 'nullable|string',
            'photo' => 'nullable|string',
            'status' => 'in:active,suspended,graduated,withdrawn',
            'medical_info' => 'nullable|array',
            'parents' => 'nullable|array',
            'parents.*.parent_id' => 'required_with:parents|integer|exists:users,id',
            'parents.*.relationship' => 'required_with:parents|in:father,mother,guardian,other',
            'parents.*.is_primary' => 'boolean',
        ]);

        $student = $this->studentService->createStudent($request->all());

        return ApiResponse::success($student, 'Élève créé avec succès', 201);
    }

    public function show(int $id): JsonResponse
    {
        $student = $this->studentService->findStudent($id);

        $this->authorize('view', $student);

        $student->load([
            'user',
            'parents',
            'currentEnrollment.classRoom',
            'enrollments.academicYear',
            'enrollments.classRoom'
        ]);

        return ApiResponse::success($student);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $student = $this->studentService->findStudent($id);
        $this->authorize('update', $student);

        $request->validate([
            'first_name' => 'sometimes|string|max:255',
            'last_name' => 'sometimes|string|max:255',
            'date_of_birth' => 'sometimes|date|before:today',
            'gender' => 'sometimes|in:M,F',
            'email' => 'sometimes|email|unique:users,email,' . $student->user_id,
            'phone' => 'nullable|string|regex:/^(\+?[0-9\s\-\(\)]*)$/|max:20',
            'place_of_birth' => 'nullable|string|max:255',
            'address' => 'nullable|string',
            'photo' => 'nullable|string',
            'status' => 'in:active,suspended,graduated,withdrawn',
            'medical_info' => 'nullable|array',
        ]);

        $updatedStudent = $this->studentService->updateStudent($id, $request->all());

        return ApiResponse::success($updatedStudent, 'Élève mis à jour avec succès');
    }

    public function destroy(int $id): JsonResponse
    {
        $student = $this->studentService->findStudent($id);
        $this->authorize('delete', $student);

        $this->studentService->deleteStudent($id);

        return ApiResponse::success(null, 'Élève supprimé avec succès', 204);
    }

    public function findByMatricule(string $matricule): JsonResponse
    {
        $student = $this->studentService->findByMatricule($matricule);

        $this->authorize('view', $student);

        return ApiResponse::success($student->load(['user', 'parents']));
    }

    public function attachParent(Request $request, int $id): JsonResponse
    {
        $student = $this->studentService->findStudent($id);
        $this->authorize('update', $student);

        $request->validate([
            'parent_id' => 'required|integer|exists:users,id',
            'relationship' => 'required|in:father,mother,guardian,other',
            'is_primary' => 'boolean',
        ]);

        $this->studentService->attachParent(
            $id,
            $request->parent_id,
            $request->relationship,
            $request->is_primary ?? false
        );

        return ApiResponse::success(null, 'Parent attaché avec succès');
    }

    public function detachParent(Request $request, int $id): JsonResponse
    {
        $student = $this->studentService->findStudent($id);
        $this->authorize('update', $student);

        $request->validate([
            'parent_id' => 'required|integer',
        ]);

        $this->studentService->detachParent($id, $request->parent_id);

        return ApiResponse::success(null, 'Parent détaché avec succès');
    }

    public function stats(Request $request): JsonResponse
    {
        $this->authorize('viewAny', \Modules\Student\Entities\Student::class);

        $stats = $this->studentService->getStudentsStats();

        return ApiResponse::success($stats);
    }

    public function export(Request $request): JsonResponse
    {
        $this->authorize('viewAny', \Modules\Student\Entities\Student::class);

        $filters = $request->only(['status', 'gender', 'search']);
        $students = $this->studentService->exportStudents($filters);

        return ApiResponse::success([
            'data' => $students,
            'count' => $students->count(),
        ], 'Données d\'export récupérées avec succès');
    }
}
