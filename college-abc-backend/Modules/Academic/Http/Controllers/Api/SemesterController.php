<?php

namespace Modules\Academic\Http\Controllers\Api;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Academic\Entities\Semester;
use Modules\Academic\Services\SemesterService;
use App\Http\Responses\ApiResponse;
use Modules\Academic\Http\Requests\StoreSemesterRequest;
use Modules\Academic\Http\Requests\UpdateSemesterRequest;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

class SemesterController extends Controller
{
    use AuthorizesRequests;

    public function __construct(
        protected SemesterService $semesterService
    ) {}

    /**
     * Display a listing of semesters
     */
    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Semester::class);

        $academicYearId = $request->input('academic_year_id');
        $type = $request->input('type');

        $query = Semester::with('academicYear')->ordered();

        if ($academicYearId) {
            $query->byAcademicYear($academicYearId);
        }

        if ($type) {
            $query->byType($type);
        }

        $semesters = $query->get();

        return ApiResponse::success($semesters);
    }

    /**
     * Store a newly created semester
     */
    public function store(StoreSemesterRequest $request): JsonResponse
    {
        // Authorization is handled in StoreSemesterRequest

        $semester = $this->semesterService->createSemester($request->validated());

        return ApiResponse::success($semester, 'Semestre créé avec succès', 201);
    }

    /**
     * Display the specified semester
     */
    public function show(int $id): JsonResponse
    {
        $semester = Semester::with(['academicYear'])->findOrFail($id);
        
        $this->authorize('view', $semester);

        return ApiResponse::success($semester);
    }

    /**
     * Update the specified semester
     */
    public function update(UpdateSemesterRequest $request, int $id): JsonResponse
    {
        // Authorization is handled in UpdateSemesterRequest for the action, 
        // but we might want to check specific policy if needed. 
        // Since logic is same as 'manage-academic', the Request check is sufficient,
        // but adding explicit policy check is safer standard practice.
        
        $semester = Semester::findOrFail($id);
        $this->authorize('update', $semester); 

        $updatedSemester = $this->semesterService->updateSemester($semester, $request->validated());

        return ApiResponse::success($updatedSemester, 'Semestre mis à jour avec succès');
    }

    /**
     * Remove the specified semester
     */
    public function destroy(int $id): JsonResponse
    {
        $semester = Semester::findOrFail($id);
        $this->authorize('delete', $semester);

        $this->semesterService->deleteSemester($semester);

        return ApiResponse::success(null, 'Semestre supprimé avec succès');
    }

    /**
     * Generate semesters for an academic year
     */
    public function generate(Request $request): JsonResponse
    {
        $this->authorize('create', Semester::class);

        $request->validate([
            'academic_year_id' => 'required|exists:academic_years,id',
            'type' => 'required|in:trimester,semester',
        ]);

        $semesters = $this->semesterService->generateSemestersForYear(
            $request->input('academic_year_id'),
            $request->input('type')
        );

        return ApiResponse::success($semesters, 'Semestres générés avec succès', 201);
    }

    /**
     * Set semester as current
     */
    public function setCurrent(int $id): JsonResponse
    {
        $semester = Semester::findOrFail($id);
        $this->authorize('update', $semester);

        $currentSemester = $this->semesterService->setCurrentSemester($semester);

        return ApiResponse::success($currentSemester, 'Semestre défini comme courant avec succès');
    }

    /**
     * Get current semester
     */
    public function current(): JsonResponse
    {
        // Public/Common access usually allowed for authenticated users
        $semester = $this->semesterService->getCurrentSemester();

        if (!$semester) {
            return ApiResponse::error('Aucun semestre courant défini', 404);
        }

        return ApiResponse::success($semester);
    }

    /**
     * Get ongoing semester
     */
    public function ongoing(): JsonResponse
    {
        // Public/Common access usually allowed for authenticated users
        $semester = $this->semesterService->getOngoingSemester();

        if (!$semester) {
            return ApiResponse::error('Aucun semestre en cours', 404);
        }

        return ApiResponse::success($semester);
    }

    /**
     * Get semesters by academic year
     */
    public function byYear(int $academicYearId): JsonResponse
    {
        $this->authorize('viewAny', Semester::class);
        
        $semesters = $this->semesterService->getSemestersByYear($academicYearId);

        return ApiResponse::success($semesters);
    }
}
