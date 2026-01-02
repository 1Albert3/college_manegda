<?php

namespace Modules\Academic\Http\Controllers\Api;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Academic\Entities\Level;
use Modules\Academic\Services\LevelService;
use Modules\Academic\Http\Requests\StoreLevelRequest;
use Modules\Academic\Http\Requests\UpdateLevelRequest;

class LevelController extends Controller
{
    public function __construct(
        protected LevelService $levelService
    ) {}

    /**
     * Display a listing of levels
     */
    public function index(Request $request): JsonResponse
    {
        $cycleId = $request->input('cycle_id');
        $activeOnly = $request->boolean('active_only', false);
        $withClassrooms = $request->boolean('with_classrooms', false);

        if ($withClassrooms) {
            $levels = $this->levelService->getAllLevelsWithClassrooms($activeOnly);
        } elseif ($cycleId) {
            $levels = $this->levelService->getLevelsByCycle($cycleId, $activeOnly);
        } else {
            $query = Level::with('cycle')->ordered();
            
            if ($activeOnly) {
                $query->active();
            }

            $levels = $query->get();
        }

        return response()->json(['success' => true, 'data' => $levels]);
    }

    /**
     * Store a newly created level
     */
    public function store(StoreLevelRequest $request): JsonResponse
    {
        $level = $this->levelService->createLevel($request->validated());

        return response()->json([
            'success' => true,
            'message' => 'Niveau créé avec succès',
            'data' => $level,
        ], 201);
    }

    /**
     * Display the specified level
     */
    public function show(int $id): JsonResponse
    {
        $level = Level::with(['cycle', 'classRooms'])->findOrFail($id);

        return response()->json(['success' => true, 'data' => $level]);
    }

    /**
     * Update the specified level
     */
    public function update(UpdateLevelRequest $request, int $id): JsonResponse
    {
        $level = Level::findOrFail($id);
        $updatedLevel = $this->levelService->updateLevel($level, $request->validated());

        return response()->json([
            'success' => true,
            'message' => 'Niveau mis à jour avec succès',
            'data' => $updatedLevel,
        ]);
    }

    /**
     * Remove the specified level
     */
    public function destroy(int $id): JsonResponse
    {
        $level = Level::findOrFail($id);
        $this->levelService->deleteLevel($level);

        return response()->json([
            'success' => true,
            'message' => 'Niveau supprimé avec succès',
        ]);
    }

    /**
     * Activate level
     */
    public function activate(int $id): JsonResponse
    {
        $level = Level::findOrFail($id);
        $activatedLevel = $this->levelService->activateLevel($level);

        return response()->json([
            'success' => true,
            'message' => 'Niveau activé avec succès',
            'data' => $activatedLevel,
        ]);
    }

    /**
     * Deactivate level
     */
    public function deactivate(int $id): JsonResponse
    {
        $level = Level::findOrFail($id);
        $deactivatedLevel = $this->levelService->deactivateLevel($level);

        return response()->json([
            'success' => true,
            'message' => 'Niveau désactivé avec succès',
            'data' => $deactivatedLevel,
        ]);
    }

    /**
     * Reorder levels within a cycle
     */
    public function reorder(Request $request): JsonResponse
    {
        $request->validate([
            'cycle_id' => 'required|exists:cycles,id',
            'orders' => 'required|array',
            'orders.*' => 'required|integer',
        ]);

        $this->levelService->reorderLevels(
            $request->input('cycle_id'),
            $request->input('orders')
        );

        return response()->json([
            'success' => true,
            'message' => 'Niveaux réorganisés avec succès',
        ]);
    }

    /**
     * Get level statistics
     */
    public function statistics(int $id): JsonResponse
    {
        $level = Level::findOrFail($id);
        $statistics = $this->levelService->getLevelStatistics($level);

        return response()->json(['success' => true, 'data' => $statistics]);
    }

    /**
     * Search levels
     */
    public function search(Request $request): JsonResponse
    {
        $request->validate([
            'term' => 'required|string|min:2',
        ]);

        $levels = $this->levelService->searchLevels($request->input('term'));

        return response()->json(['success' => true, 'data' => $levels]);
    }
}
