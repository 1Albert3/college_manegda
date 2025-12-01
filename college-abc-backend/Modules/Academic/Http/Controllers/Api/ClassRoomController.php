<?php

namespace Modules\Academic\Http\Controllers\Api;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Academic\Services\ClassRoomService;
use App\Http\Responses\ApiResponse;

/**
 * @group Academic Management
 * Gestion des classes
 */
class ClassRoomController extends Controller
{
    public function __construct(
        private ClassRoomService $classRoomService
    ) {
        $this->middleware('permission:view-academic')->only(['index', 'show', 'byLevel', 'byStream', 'active', 'grouped', 'stats', 'findByName', 'students', 'subjects', 'canDelete', 'attendanceStats']);
        $this->middleware('permission:manage-academic')->except(['index', 'show', 'byLevel', 'byStream', 'active', 'grouped', 'stats', 'findByName', 'students', 'subjects', 'canDelete', 'attendanceStats']);
    }

    public function index(Request $request): JsonResponse
    {
        $filters = $request->only(['level', 'stream', 'status', 'search', 'per_page']);

        $classRooms = $this->classRoomService->getClassRooms($filters);

        return ApiResponse::paginated($classRooms);
    }

    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'name' => 'nullable|string|max:255|unique:classes,name',
            'level' => 'required|string|max:50',
            'stream' => 'nullable|string|max:50',
            'status' => 'in:active,inactive,archived',
            'capacity' => 'nullable|integer|min:0',
            'description' => 'nullable|string|max:1000',
            'academic_year_id' => 'nullable|integer|exists:academic_years,id',
        ]);

        // Validation personnalisée
        $validationErrors = $this->classRoomService->validateClassRoomData($request->all());
        if (!empty($validationErrors)) {
            return ApiResponse::error('Erreurs de validation: ' . implode(', ', $validationErrors), 422);
        }

        $classRoom = $this->classRoomService->createClassRoom($request->all());

        return ApiResponse::success($classRoom, 'Classe créée avec succès', 201);
    }

    public function show(int $id): JsonResponse
    {
        $classRoom = $this->classRoomService->findClassRoom($id);

        return ApiResponse::success($classRoom->load(['subjects', 'academicYear', 'enrollments.student']));
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $request->validate([
            'name' => 'nullable|string|max:255|unique:classes,name,' . $id,
            'level' => 'required|string|max:50',
            'stream' => 'nullable|string|max:50',
            'status' => 'in:active,inactive,archived',
            'capacity' => 'nullable|integer|min:0',
            'description' => 'nullable|string|max:1000',
            'academic_year_id' => 'nullable|integer|exists:academic_years,id',
        ]);

        $classRoom = $this->classRoomService->updateClassRoom($id, $request->all());

        return ApiResponse::success($classRoom, 'Classe mise à jour avec succès');
    }

    public function destroy(int $id): JsonResponse
    {
        if (!$this->classRoomService->canDeleteClass($id)) {
            return ApiResponse::error('Impossible de supprimer cette classe car elle contient des étudiants actifs ou est assignée à des matières.', 422);
        }

        $this->classRoomService->deleteClassRoom($id);

        return ApiResponse::success(null, 'Classe supprimée avec succès', 204);
    }

    public function byLevel(string $level): JsonResponse
    {
        $classRooms = $this->classRoomService->getClassRoomsByLevel($level);

        return ApiResponse::success($classRooms);
    }

    public function byStream(string $stream): JsonResponse
    {
        $classRooms = $this->classRoomService->getClassRoomsByStream($stream);

        return ApiResponse::success($classRooms);
    }

    public function active(Request $request): JsonResponse
    {
        $classRooms = $this->classRoomService->getActiveClassRooms();

        return ApiResponse::success($classRooms);
    }

    public function grouped(Request $request): JsonResponse
    {
        $grouped = $this->classRoomService->getClassRoomsGroupedByLevel();

        return ApiResponse::success($grouped);
    }

    public function stats(Request $request): JsonResponse
    {
        $stats = $this->classRoomService->getClassRoomsStats();

        return ApiResponse::success($stats);
    }

    public function bulkStatusUpdate(Request $request): JsonResponse
    {
        $request->validate([
            'class_ids' => 'required|array|min:1',
            'class_ids.*' => 'integer|exists:classes,id',
            'status' => 'required|in:active,inactive,archived',
        ]);

        try {
            $updated = $this->classRoomService->bulkStatusUpdate($request->class_ids, $request->status);

            return ApiResponse::success(['updated' => $updated], "$updated classe(s) mise(s) à jour");
        } catch (\Exception $e) {
            return ApiResponse::error($e->getMessage(), 422);
        }
    }

    public function assignSubject(Request $request, int $id): JsonResponse
    {
        $request->validate([
            'subject_id' => 'required|integer|exists:subjects,id',
            'attributes' => 'nullable|array',
        ]);

        $this->classRoomService->assignSubject($id, $request->subject_id, $request->attributes ?? []);

        return ApiResponse::success(null, 'Matière assignée à la classe avec succès');
    }

    public function removeSubject(Request $request, int $id): JsonResponse
    {
        $request->validate([
            'subject_id' => 'required|integer|exists:subjects,id',
        ]);

        $this->classRoomService->removeSubject($id, $request->subject_id);

        return ApiResponse::success(null, 'Matière retirée de la classe avec succès');
    }

    public function enrollStudent(Request $request, int $id): JsonResponse
    {
        $request->validate([
            'student_id' => 'required|integer|exists:students,id',
            'academic_year_id' => 'nullable|integer|exists:academic_years,id',
            'enrolled_at' => 'nullable|date',
            'notes' => 'nullable|string|max:1000',
        ]);

        $enrollmentData = $request->only(['academic_year_id', 'enrolled_at', 'notes']);

        try {
            $enrollment = $this->classRoomService->enrollStudent($id, $request->student_id, $enrollmentData);

            return ApiResponse::success($enrollment, 'Étudiant inscrit avec succès');
        } catch (\Exception $e) {
            return ApiResponse::error($e->getMessage(), 422);
        }
    }

    public function updateStudentsCount(Request $request, int $id): JsonResponse
    {
        $classRoom = $this->classRoomService->updateStudentsCount($id);

        return ApiResponse::success($classRoom, 'Nombre d\'étudiants mis à jour avec succès');
    }

    public function attendanceStats(Request $request, int $id): JsonResponse
    {
        $stats = $this->classRoomService->getAttendanceStats($id);

        return ApiResponse::success($stats);
    }

    public function students(Request $request, int $id): JsonResponse
    {
        $classRoom = $this->classRoomService->findClassRoom($id);

        $students = $classRoom->currentStudents();

        return ApiResponse::success($students);
    }

    public function subjects(Request $request, int $id): JsonResponse
    {
        $classRoom = $this->classRoomService->findClassRoom($id);

        $subjects = $classRoom->currentSubjects();

        return ApiResponse::success($subjects);
    }

    public function canDelete(Request $request, int $id): JsonResponse
    {
        $canDelete = $this->classRoomService->canDeleteClass($id);

        return ApiResponse::success(['can_delete' => $canDelete]);
    }

    public function findByName(string $name): JsonResponse
    {
        try {
            $classRoom = $this->classRoomService->findByName($name);

            return ApiResponse::success($classRoom->load(['subjects', 'academicYear']));
        } catch (\Exception $e) {
            return ApiResponse::error($e->getMessage(), 404);
        }
    }
}
