<?php

namespace Modules\Attendance\Http\Controllers\Api;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Attendance\Services\AttendanceService;
use App\Http\Responses\ApiResponse;
use Spatie\QueryBuilder\QueryBuilder;
use Spatie\QueryBuilder\AllowedFilter;

/**
 * @group Attendance Management
 * Gestion des présences des élèves
 */
class AttendanceController extends Controller
{
    use \Illuminate\Foundation\Auth\Access\AuthorizesRequests;

    public function __construct(
        private AttendanceService $attendanceService
    ) {
        // Middleware removed in favor of strict Policy checks in methods
    }

    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', \Modules\Attendance\Entities\Attendance::class);

        $attendances = QueryBuilder::for(\Modules\Attendance\Entities\Attendance::class)
            ->allowedFilters([
                'status',
                AllowedFilter::exact('student_id'),
                AllowedFilter::exact('session_id'),
                AllowedFilter::scope('by_class'),
                AllowedFilter::scope('by_date'),
            ])
            ->allowedIncludes(['student', 'session.subject', 'session.class', 'recordedBy', 'justification'])
            ->allowedSorts(['recorded_at', 'status', 'created_at'])
            ->with(['student:id,first_name,last_name,matricule', 'session:id,session_date', 'recordedBy:id,name'])
            ->paginate($request->get('per_page', 15));

        return ApiResponse::paginated($attendances);
    }

    public function show(string $uuid): JsonResponse
    {
        $attendance = \Modules\Attendance\Entities\Attendance::where('uuid', $uuid)->first();

        if (!$attendance) {
            return ApiResponse::error('Présence non trouvée', 404);
        }

        $this->authorize('view', $attendance);

        return ApiResponse::success($attendance->load([
            'student',
            'session.subject',
            'session.class',
            'session.teacher',
            'recordedBy',
            'justification'
        ]));
    }

    public function mark(Request $request): JsonResponse
    {
        $this->authorize('create', \Modules\Attendance\Entities\Attendance::class);

        $request->validate([
            'student_id' => 'required|integer|exists:students,id',
            'session_id' => 'required|integer|exists:sessions,id',
            'status' => 'required|in:present,absent,late,excused,partially_present',
            'minutes_late' => 'nullable|integer|min:0|max:480',
            'absence_reason' => 'nullable|in:illness,family_emergency,personal_reasons,transport_issues,other',
            'absence_notes' => 'nullable|string|max:500',
            'teacher_notes' => 'nullable|string|max:500',
            'metadata' => 'nullable|array',
        ]);

        try {
            $attendance = $this->attendanceService->markAttendance(
                $request->student_id,
                $request->session_id,
                $request->only([
                    'status',
                    'minutes_late',
                    'absence_reason',
                    'absence_notes',
                    'teacher_notes',
                    'metadata'
                ])
            );

            return ApiResponse::success($attendance->load([
                'student:id,first_name,last_name,matricule',
                'session:id,session_date',
                'recordedBy:id,name'
            ]), 'Présence marquée avec succès');
        } catch (\Exception $e) {
            return ApiResponse::error('Erreur lors du marquage de la présence: ' . $e->getMessage(), 500);
        }
    }

    public function bulkMark(Request $request): JsonResponse
    {
        $this->authorize('create', \Modules\Attendance\Entities\Attendance::class);

        $request->validate([
            'session_id' => 'required|integer|exists:sessions,id',
            'attendances' => 'required|array|min:1',
            'attendances.*.student_id' => 'required|integer|exists:students,id',
            'attendances.*.status' => 'required|in:present,absent,late,excused,partially_present',
            'attendances.*.minutes_late' => 'nullable|integer|min:0|max:480',
            'attendances.*.absence_reason' => 'nullable|in:illness,family_emergency,personal_reasons,transport_issues,other',
            'attendances.*.absence_notes' => 'nullable|string|max:500',
            'attendances.*.teacher_notes' => 'nullable|string|max:500',
            'attendances.*.metadata' => 'nullable|array',
        ]);

        try {
            $attendances = $this->attendanceService->bulkMarkAttendance(
                $request->session_id,
                $request->attendances
            );

            return ApiResponse::success([
                'attendances' => $attendances->load([
                    'student:id,first_name,last_name,matricule',
                    'recordedBy:id,name'
                ]),
                'count' => $attendances->count(),
            ], 'Présences marquées avec succès');
        } catch (\Exception $e) {
            return ApiResponse::error('Erreur lors du marquage des présences: ' . $e->getMessage(), 500);
        }
    }

    public function update(Request $request, string $uuid): JsonResponse
    {
        $attendance = \Modules\Attendance\Entities\Attendance::where('uuid', $uuid)->firstOrFail();
        $this->authorize('update', $attendance);

        $request->validate([
            'status' => 'sometimes|in:present,absent,late,excused,partially_present',
            'minutes_late' => 'nullable|integer|min:0|max:480',
            'absence_reason' => 'nullable|in:illness,family_emergency,personal_reasons,transport_issues,other',
            'absence_notes' => 'nullable|string|max:500',
            'teacher_notes' => 'nullable|string|max:500',
            'admin_notes' => 'nullable|string|max:500',
            'metadata' => 'nullable|array',
        ]);

        try {
            $attendance->update($request->validated());

            return ApiResponse::success($attendance->fresh()->load([
                'student:id,first_name,last_name,matricule',
                'session:id,session_date',
                'recordedBy:id,name'
            ]), 'Présence mise à jour avec succès');
        } catch (\Exception $e) {
            return ApiResponse::error('Erreur lors de la mise à jour: ' . $e->getMessage(), 500);
        }
    }

    public function destroy(string $uuid): JsonResponse
    {
        $attendance = \Modules\Attendance\Entities\Attendance::where('uuid', $uuid)->firstOrFail();
        $this->authorize('delete', $attendance);

        try {
            $attendance->delete();

            return ApiResponse::success(null, 'Présence supprimée avec succès', 204);
        } catch (\Exception $e) {
            return ApiResponse::error('Erreur lors de la suppression: ' . $e->getMessage(), 500);
        }
    }

    public function byStudent(int $studentId, Request $request): JsonResponse
    {
        $student = \Modules\Student\Entities\Student::findOrFail($studentId);
        // Authorization: Check relationship or permission
        if ($request->user()->cant('view', $student) && $request->user()->cant('view-attendances')) {
            abort(403);
        }

        $startDate = $request->get('start_date');
        $endDate = $request->get('end_date');

        $attendances = $this->attendanceService->getStudentAttendance($studentId, $startDate, $endDate);

        return ApiResponse::success([
            'attendances' => $attendances,
            'stats' => $this->attendanceService->getAttendanceStats($studentId, $startDate, $endDate),
        ]);
    }

    public function bySession(int $sessionId): JsonResponse
    {
        $this->authorize('viewAny', \Modules\Attendance\Entities\Attendance::class);

        $attendances = $this->attendanceService->getSessionAttendance($sessionId);

        return ApiResponse::success($attendances->load([
            'student:id,first_name,last_name,matricule',
            'recordedBy:id,name'
        ]));
    }

    public function byClass(int $classId, Request $request): JsonResponse
    {
        $this->authorize('viewAny', \Modules\Attendance\Entities\Attendance::class);

        $request->validate([
            'date' => 'required|date',
        ]);

        $attendances = $this->attendanceService->getClassAttendance($classId, $request->date);

        return ApiResponse::success($attendances->load([
            'student:id,first_name,last_name,matricule',
            'session.subject:id,name',
            'recordedBy:id,name'
        ]));
    }

    public function monthlyReport(Request $request): JsonResponse
    {
        $this->authorize('viewAny', \Modules\Attendance\Entities\Attendance::class);

        $request->validate([
            'class_id' => 'required|integer|exists:class_rooms,id',
            'month' => 'required|integer|min:1|max:12',
            'year' => 'required|integer|min:2020|max:' . (date('Y') + 1),
        ]);

        $report = $this->attendanceService->getMonthlyReport(
            $request->class_id,
            $request->month,
            $request->year
        );

        return ApiResponse::success([
            'report' => $report,
            'summary' => [
                'total_students' => count($report),
                'average_rate' => count($report) > 0 ? round(collect($report)->avg('attendance_rate'), 2) : 0,
            ]
        ]);
    }

    public function stats(Request $request): JsonResponse
    {
        if ($request->student_id) {
            // Check student access
            $student = \Modules\Student\Entities\Student::findOrFail($request->student_id);
            if ($request->user()->cant('view', $student) && $request->user()->cant('view-attendances')) {
                abort(403);
            }
        } else {
            $this->authorize('viewAny', \Modules\Attendance\Entities\Attendance::class);
        }

        $request->validate([
            'student_id' => 'nullable|integer|exists:students,id',
            'class_id' => 'nullable|integer|exists:class_rooms,id',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
        ]);

        if ($request->student_id) {
            $stats = $this->attendanceService->getAttendanceStats(
                $request->student_id,
                $request->start_date,
                $request->end_date
            );
        } elseif ($request->class_id) {
            $stats = $this->attendanceService->getClassAttendanceStats(
                $request->class_id,
                $request->start_date,
                $request->end_date
            );
        } else {
            return ApiResponse::error('Veuillez spécifier un étudiant ou une classe', 400);
        }

        return ApiResponse::success($stats);
    }

    public function trends(Request $request): JsonResponse
    {
        $this->authorize('viewAny', \Modules\Attendance\Entities\Attendance::class);

        $request->validate([
            'class_id' => 'required|integer|exists:class_rooms,id',
            'days' => 'nullable|integer|min:1|max:365',
        ]);

        $trends = $this->attendanceService->getAttendanceTrends(
            $request->class_id,
            $request->get('days', 30)
        );

        return ApiResponse::success($trends);
    }

    public function studentsAtRisk(Request $request): JsonResponse
    {
        $this->authorize('viewAny', \Modules\Attendance\Entities\Attendance::class);

        $request->validate([
            'class_id' => 'required|integer|exists:class_rooms,id',
            'threshold' => 'nullable|numeric|min:0|max:100',
        ]);

        $students = $this->attendanceService->getStudentsAtRisk(
            $request->class_id,
            $request->get('threshold', 75.0)
        );

        return ApiResponse::success($students);
    }

    public function sendAbsenceNotifications(Request $request): JsonResponse
    {
        $this->authorize('create', \Modules\Attendance\Entities\Attendance::class); // or 'send-communications'

        $request->validate([
            'date' => 'required|date',
        ]);

        try {
            $sentCount = $this->attendanceService->sendBulkAbsenceNotifications($request->date);

            return ApiResponse::success([
                'sent_count' => $sentCount,
            ], 'Notifications envoyées avec succès');
        } catch (\Exception $e) {
            return ApiResponse::error('Erreur lors de l\'envoi des notifications: ' . $e->getMessage(), 500);
        }
    }

    public function export(Request $request): JsonResponse
    {
        $this->authorize('viewAny', \Modules\Attendance\Entities\Attendance::class);

        $request->validate([
            'class_id' => 'required|integer|exists:class_rooms,id',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
        ]);

        $data = $this->attendanceService->exportAttendanceData(
            $request->class_id,
            $request->start_date,
            $request->end_date
        );

        return ApiResponse::success([
            'data' => $data,
            'count' => count($data),
        ], 'Données d\'export récupérées avec succès');
    }
}
