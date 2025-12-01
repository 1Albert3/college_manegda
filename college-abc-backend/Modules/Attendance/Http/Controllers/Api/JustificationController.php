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
 * Gestion des justifications d'absence
 */
class JustificationController extends Controller
{
    public function __construct(
        private AttendanceService $attendanceService
    ) {
        $this->middleware('permission:view-justifications')->only(['index', 'show']);
        $this->middleware('permission:submit-justifications')->only(['store']);
        $this->middleware('permission:manage-justifications')->only(['approve', 'reject', 'update', 'destroy']);
    }

    public function index(Request $request): JsonResponse
    {
        $justifications = QueryBuilder::for(\Modules\Attendance\Entities\Justification::class)
            ->allowedFilters([
                'status',
                'type',
                AllowedFilter::exact('attendance_id'),
                AllowedFilter::exact('submitted_by'),
                AllowedFilter::exact('approved_by'),
            ])
            ->allowedIncludes(['attendance.student', 'attendance.session', 'submittedBy', 'approvedBy'])
            ->allowedSorts(['submitted_at', 'status', 'created_at'])
            ->with(['attendance.student:id,first_name,last_name,matricule', 'submittedBy:id,name'])
            ->paginate($request->get('per_page', 15));

        return ApiResponse::paginated($justifications);
    }

    public function show(string $uuid): JsonResponse
    {
        $justification = \Modules\Attendance\Entities\Justification::with([
            'attendance.student',
            'attendance.session.subject',
            'attendance.session.class',
            'submittedBy',
            'approvedBy'
        ])->where('uuid', $uuid)->firstOrFail();

        return ApiResponse::success($justification);
    }

    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'attendance_id' => 'required|integer|exists:attendances,id',
            'type' => 'required|in:medical_certificate,parental_note,administrative,other',
            'reason' => 'nullable|string|max:255',
            'description' => 'nullable|string|max:1000',
            'documents' => 'nullable|array',
            'documents.*.name' => 'required_with:documents|string',
            'documents.*.path' => 'required_with:documents|string',
            'documents.*.type' => 'required_with:documents|string',
            'medical_certificate_path' => 'nullable|string',
        ]);

        try {
            $justification = $this->attendanceService->submitJustification(
                $request->attendance_id,
                $request->only([
                    'type',
                    'reason',
                    'description',
                    'documents',
                    'medical_certificate_path'
                ])
            );

            return ApiResponse::success($justification->load([
                'attendance.student:id,first_name,last_name,matricule',
                'submittedBy:id,name'
            ]), 'Justification soumise avec succès', 201);
        } catch (\Exception $e) {
            return ApiResponse::error('Erreur lors de la soumission: ' . $e->getMessage(), 400);
        }
    }

    public function update(Request $request, string $uuid): JsonResponse
    {
        $request->validate([
            'type' => 'sometimes|in:medical_certificate,parental_note,administrative,other',
            'reason' => 'nullable|string|max:255',
            'description' => 'nullable|string|max:1000',
            'documents' => 'nullable|array',
            'documents.*.name' => 'required_with:documents|string',
            'documents.*.path' => 'required_with:documents|string',
            'documents.*.type' => 'required_with:documents|string',
            'medical_certificate_path' => 'nullable|string',
            'admin_notes' => 'nullable|string|max:500',
        ]);

        try {
            $justification = \Modules\Attendance\Entities\Justification::where('uuid', $uuid)->firstOrFail();
            $justification->update($request->validated());

            return ApiResponse::success($justification->fresh()->load([
                'attendance.student:id,first_name,last_name,matricule',
                'submittedBy:id,name'
            ]), 'Justification mise à jour avec succès');
        } catch (\Exception $e) {
            return ApiResponse::error('Erreur lors de la mise à jour: ' . $e->getMessage(), 500);
        }
    }

    public function destroy(string $uuid): JsonResponse
    {
        try {
            $justification = \Modules\Attendance\Entities\Justification::where('uuid', $uuid)->firstOrFail();

            // Vérifier si la justification peut être supprimée
            if ($justification->isApproved()) {
                return ApiResponse::error('Impossible de supprimer une justification approuvée', 400);
            }

            $justification->delete();

            return ApiResponse::success(null, 'Justification supprimée avec succès', 204);
        } catch (\Exception $e) {
            return ApiResponse::error('Erreur lors de la suppression: ' . $e->getMessage(), 500);
        }
    }

    public function approve(string $uuid, Request $request): JsonResponse
    {
        $request->validate([
            'notes' => 'nullable|string|max:500',
        ]);

        try {
            $justification = \Modules\Attendance\Entities\Justification::where('uuid', $uuid)->firstOrFail();

            $justification = $this->attendanceService->approveJustification(
                $justification->id,
                $request->notes
            );

            return ApiResponse::success($justification->load([
                'attendance.student:id,first_name,last_name,matricule',
                'approvedBy:id,name'
            ]), 'Justification approuvée avec succès');
        } catch (\Exception $e) {
            return ApiResponse::error('Erreur lors de l\'approbation: ' . $e->getMessage(), 400);
        }
    }

    public function reject(string $uuid, Request $request): JsonResponse
    {
        $request->validate([
            'notes' => 'nullable|string|max:500',
        ]);

        try {
            $justification = \Modules\Attendance\Entities\Justification::where('uuid', $uuid)->firstOrFail();

            $justification = $this->attendanceService->rejectJustification(
                $justification->id,
                $request->notes
            );

            return ApiResponse::success($justification->load([
                'attendance.student:id,first_name,last_name,matricule',
                'approvedBy:id,name'
            ]), 'Justification rejetée avec succès');
        } catch (\Exception $e) {
            return ApiResponse::error('Erreur lors du rejet: ' . $e->getMessage(), 400);
        }
    }

    public function pending(Request $request): JsonResponse
    {
        $justifications = QueryBuilder::for(\Modules\Attendance\Entities\Justification::class)
            ->where('status', 'pending')
            ->allowedIncludes(['attendance.student', 'attendance.session', 'submittedBy'])
            ->allowedSorts(['submitted_at', 'created_at'])
            ->with(['attendance.student:id,first_name,last_name,matricule', 'submittedBy:id,name'])
            ->paginate($request->get('per_page', 15));

        return ApiResponse::paginated($justifications);
    }

    public function byStudent(int $studentId, Request $request): JsonResponse
    {
        $justifications = QueryBuilder::for(\Modules\Attendance\Entities\Justification::class)
            ->whereHas('attendance', function ($q) use ($studentId) {
                $q->where('student_id', $studentId);
            })
            ->allowedFilters(['status', 'type'])
            ->allowedIncludes(['attendance.session', 'submittedBy', 'approvedBy'])
            ->allowedSorts(['submitted_at', 'status', 'created_at'])
            ->with(['attendance.session.subject:id,name', 'submittedBy:id,name', 'approvedBy:id,name'])
            ->paginate($request->get('per_page', 15));

        return ApiResponse::paginated($justifications);
    }

    public function stats(Request $request): JsonResponse
    {
        $stats = \Modules\Attendance\Entities\Justification::selectRaw('
            COUNT(*) as total,
            SUM(CASE WHEN status = "pending" THEN 1 ELSE 0 END) as pending_count,
            SUM(CASE WHEN status = "approved" THEN 1 ELSE 0 END) as approved_count,
            SUM(CASE WHEN status = "rejected" THEN 1 ELSE 0 END) as rejected_count,
            SUM(CASE WHEN status = "under_review" THEN 1 ELSE 0 END) as under_review_count
        ')->first();

        $typeStats = \Modules\Attendance\Entities\Justification::selectRaw('
            type,
            COUNT(*) as count,
            SUM(CASE WHEN status = "approved" THEN 1 ELSE 0 END) as approved_count
        ')
        ->groupBy('type')
        ->get()
        ->keyBy('type');

        return ApiResponse::success([
            'overall' => $stats,
            'by_type' => $typeStats,
        ]);
    }

    public function addDocument(Request $request, string $uuid): JsonResponse
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'path' => 'required|string',
            'type' => 'required|string|max:50',
        ]);

        try {
            $justification = \Modules\Attendance\Entities\Justification::where('uuid', $uuid)->firstOrFail();
            $justification->addDocument($request->path, $request->type);

            return ApiResponse::success(null, 'Document ajouté avec succès');
        } catch (\Exception $e) {
            return ApiResponse::error('Erreur lors de l\'ajout du document: ' . $e->getMessage(), 500);
        }
    }

    public function removeDocument(Request $request, string $uuid): JsonResponse
    {
        $request->validate([
            'index' => 'required|integer|min:0',
        ]);

        try {
            $justification = \Modules\Attendance\Entities\Justification::where('uuid', $uuid)->firstOrFail();
            $justification->removeDocument($request->index);

            return ApiResponse::success(null, 'Document supprimé avec succès');
        } catch (\Exception $e) {
            return ApiResponse::error('Erreur lors de la suppression du document: ' . $e->getMessage(), 500);
        }
    }
}
