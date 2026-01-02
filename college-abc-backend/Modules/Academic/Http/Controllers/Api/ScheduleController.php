<?php

namespace Modules\Academic\Http\Controllers\Api;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Academic\Entities\Schedule;
use Modules\Academic\Services\ScheduleService;

class ScheduleController extends Controller
{
    use \Illuminate\Foundation\Auth\Access\AuthorizesRequests;

    public function __construct(
        protected ScheduleService $scheduleService
    ) {}

    /**
     * Display a listing of schedules
     */
    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Schedule::class);

        try {
            $query = Schedule::with(['classRoom', 'subject', 'teacher', 'academicYear']);

            if ($classRoomId = $request->input('class_room_id')) {
                $query->byClass($classRoomId);
            }

            if ($teacherId = $request->input('teacher_id')) {
                $query->byTeacher($teacherId);
            }

            if ($subjectId = $request->input('subject_id')) {
                $query->bySubject($subjectId);
            }

            if ($day = $request->input('day')) {
                $query->byDay($day);
            }

            if ($academicYearId = $request->input('academic_year_id')) {
                $query->byAcademicYear($academicYearId);
            }

            $schedules = $query->ordered()->get();

            return response()->json(['data' => $schedules]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Erreur lors de la récupération des emplois du temps',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Store a newly created schedule
     */
    public function store(Request $request): JsonResponse
    {
        $this->authorize('create', Schedule::class);

        $request->validate([
            'class_room_id' => 'required|exists:class_rooms,id',
            'subject_id' => 'required|exists:subjects,id',
            'teacher_id' => 'required|exists:users,id',
            'academic_year_id' => 'nullable|exists:academic_years,id',
            'day_of_week' => 'required|in:monday,tuesday,wednesday,thursday,friday,saturday',
            'start_time' => 'required|date_format:H:i',
            'end_time' => 'required|date_format:H:i|after:start_time',
            'room' => 'nullable|string|max:100',
            'notes' => 'nullable|string',
        ]);

        try {
            $schedule = $this->scheduleService->createSchedule($request->all());

            return response()->json([
                'message' => 'Emploi du temps créé avec succès',
                'data' => $schedule,
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Erreur lors de la création de l\'emploi du temps',
                'error' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Display the specified schedule
     */
    public function show(int $id): JsonResponse
    {
        try {
            $schedule = Schedule::with(['classRoom', 'subject', 'teacher', 'academicYear'])
                ->findOrFail($id);

            $this->authorize('view', $schedule);

            return response()->json(['data' => $schedule]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Emploi du temps non trouvé',
                'error' => $e->getMessage(),
            ], 404);
        }
    }

    /**
     * Update the specified schedule
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $schedule = Schedule::findOrFail($id);
        $this->authorize('update', $schedule);

        $request->validate([
            'class_room_id' => 'sometimes|required|exists:class_rooms,id',
            'subject_id' => 'sometimes|required|exists:subjects,id',
            'teacher_id' => 'sometimes|required|exists:users,id',
            'day_of_week' => 'sometimes|required|in:monday,tuesday,wednesday,thursday,friday,saturday',
            'start_time' => 'sometimes|required|date_format:H:i',
            'end_time' => 'sometimes|required|date_format:H:i|after:start_time',
            'room' => 'sometimes|nullable|string|max:100',
            'notes' => 'sometimes|nullable|string',
        ]);

        try {
            $updatedSchedule = $this->scheduleService->updateSchedule($schedule, $request->all());

            return response()->json([
                'message' => 'Emploi du temps mis à jour avec succès',
                'data' => $updatedSchedule,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Erreur lors de la mise à jour de l\'emploi du temps',
                'error' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Remove the specified schedule
     */
    public function destroy(int $id): JsonResponse
    {
        $schedule = Schedule::findOrFail($id);
        $this->authorize('delete', $schedule);

        try {
            $this->scheduleService->deleteSchedule($schedule);

            return response()->json([
                'message' => 'Emploi du temps supprimé avec succès',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Erreur lors de la suppression de l\'emploi du temps',
                'error' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Get class schedule
     */
    public function classSchedule(int $classRoomId, Request $request): JsonResponse
    {
        $this->authorize('viewAny', Schedule::class); // Or more specific check

        try {
            $academicYearId = $request->input('academic_year_id');
            $schedule = $this->scheduleService->getClassSchedule($classRoomId, $academicYearId);

            return response()->json(['data' => $schedule]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Erreur lors de la récupération de l\'emploi du temps de la classe',
                'error' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Get teacher schedule
     */
    public function teacherSchedule(int $teacherId, Request $request): JsonResponse
    {
        $this->authorize('viewAny', Schedule::class);
        // Teacher viewing own
        if ($request->user()->hasRole('teacher') && $request->user()->id !== $teacherId) {
            abort(403);
        }

        try {
            $academicYearId = $request->input('academic_year_id');
            $schedule = $this->scheduleService->getTeacherSchedule($teacherId, $academicYearId);

            return response()->json(['data' => $schedule]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Erreur lors de la récupération de l\'emploi du temps du professeur',
                'error' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Get today's schedule for a class
     */
    public function todayClass(int $classRoomId): JsonResponse
    {
        $this->authorize('viewAny', Schedule::class);

        try {
            $schedule = $this->scheduleService->getTodayClassSchedule($classRoomId);

            return response()->json(['data' => $schedule]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Erreur lors de la récupération de l\'emploi du temps du jour',
                'error' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Get today's schedule for a teacher
     */
    public function todayTeacher(int $teacherId): JsonResponse
    {
        $this->authorize('viewAny', Schedule::class);

        try {
            $schedule = $this->scheduleService->getTodayTeacherSchedule($teacherId);

            return response()->json(['data' => $schedule]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Erreur lors de la récupération de l\'emploi du temps du jour',
                'error' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Bulk create schedules for a class
     */
    public function bulkCreate(Request $request): JsonResponse
    {
        $this->authorize('create', Schedule::class);

        $request->validate([
            'class_room_id' => 'required|exists:class_rooms,id',
            'schedules' => 'required|array|min:1',
            'schedules.*.subject_id' => 'required|exists:subjects,id',
            'schedules.*.teacher_id' => 'required|exists:users,id',
            'schedules.*.day_of_week' => 'required|in:monday,tuesday,wednesday,thursday,friday,saturday',
            'schedules.*.start_time' => 'required|date_format:H:i',
            'schedules.*.end_time' => 'required|date_format:H:i',
            'schedules.*.room' => 'nullable|string|max:100',
        ]);

        try {
            $created = $this->scheduleService->bulkCreateForClass(
                $request->input('class_room_id'),
                $request->input('schedules')
            );

            return response()->json([
                'message' => 'Emplois du temps créés avec succès',
                'data' => $created,
                'count' => count($created),
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Erreur lors de la création en masse',
                'error' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Copy schedule to new academic year
     */
    public function copyToNewYear(Request $request): JsonResponse
    {
        $this->authorize('create', Schedule::class);

        $request->validate([
            'from_year_id' => 'required|exists:academic_years,id',
            'to_year_id' => 'required|exists:academic_years,id',
            'class_room_id' => 'nullable|exists:class_rooms,id',
        ]);

        try {
            $copied = $this->scheduleService->copyScheduleToNewYear(
                $request->input('from_year_id'),
                $request->input('to_year_id'),
                $request->input('class_room_id')
            );

            return response()->json([
                'message' => 'Emplois du temps copiés avec succès',
                'count' => $copied,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Erreur lors de la copie des emplois du temps',
                'error' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Get schedule statistics
     */
    public function statistics(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Schedule::class); // Probably admin/staff

        try {
            $academicYearId = $request->input('academic_year_id');
            $statistics = $this->scheduleService->getStatistics($academicYearId);

            return response()->json(['data' => $statistics]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Erreur lors de la récupération des statistiques',
                'error' => $e->getMessage(),
            ], 400);
        }
    }
}
