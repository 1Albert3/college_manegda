<?php

namespace Modules\Academic\Http\Controllers\Api;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Academic\Entities\Cycle;
use Modules\Academic\Services\CycleService;
use Modules\Academic\Http\Requests\StoreCycleRequest;
use Modules\Academic\Http\Requests\UpdateCycleRequest;

class CycleController extends Controller
{
    public function __construct(
        protected CycleService $cycleService
    ) {}

    /**
     * Display a listing of cycles
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $activeOnly = $request->boolean('active_only', false);
            $withLevels = $request->boolean('with_levels', false);

            if ($withLevels) {
                $cycles = $this->cycleService->getAllCyclesWithLevels($activeOnly);
            } else {
                $query = Cycle::ordered();
                
                if ($activeOnly) {
                    $query->active();
                }

                $cycles = $query->get();
            }

            return response()->json([
                'data' => $cycles,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Erreur lors de la récupération des cycles',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Store a newly created cycle
     *
     * @param StoreCycleRequest $request
     * @return JsonResponse
     */
    public function store(StoreCycleRequest $request): JsonResponse
    {
        try {
            $cycle = $this->cycleService->createCycle($request->validated());

            return response()->json([
                'message' => 'Cycle créé avec succès',
                'data' => $cycle,
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Erreur lors de la création du cycle',
                'error' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Display the specified cycle
     *
     * @param int $id
     * @return JsonResponse
     */
    public function show(int $id): JsonResponse
    {
        try {
            $cycle = Cycle::with(['levels'])->findOrFail($id);

            return response()->json([
                'data' => $cycle,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Cycle non trouvé',
                'error' => $e->getMessage(),
            ], 404);
        }
    }

    /**
     * Update the specified cycle
     *
     * @param UpdateCycleRequest $request
     * @param int $id
     * @return JsonResponse
     */
    public function update(UpdateCycleRequest $request, int $id): JsonResponse
    {
        try {
            $cycle = Cycle::findOrFail($id);
            $updatedCycle = $this->cycleService->updateCycle($cycle, $request->validated());

            return response()->json([
                'message' => 'Cycle mis à jour avec succès',
                'data' => $updatedCycle,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Erreur lors de la mise à jour du cycle',
                'error' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Remove the specified cycle
     *
     * @param int $id
     * @return JsonResponse
     */
    public function destroy(int $id): JsonResponse
    {
        try {
            $cycle = Cycle::findOrFail($id);
            $this->cycleService->deleteCycle($cycle);

            return response()->json([
                'message' => 'Cycle supprimé avec succès',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Erreur lors de la suppression du cycle',
                'error' => $e->getMessage(),
            ], 409);
        }
    }

    /**
     * Activate cycle
     *
     * @param int $id
     * @return JsonResponse
     */
    public function activate(int $id): JsonResponse
    {
        try {
            $cycle = Cycle::findOrFail($id);
            $activatedCycle = $this->cycleService->activateCycle($cycle);

            return response()->json([
                'message' => 'Cycle activé avec succès',
                'data' => $activatedCycle,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Erreur lors de l\'activation du cycle',
                'error' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Deactivate cycle
     *
     * @param int $id
     * @return JsonResponse
     */
    public function deactivate(int $id): JsonResponse
    {
        try {
            $cycle = Cycle::findOrFail($id);
            $deactivatedCycle = $this->cycleService->deactivateCycle($cycle);

            return response()->json([
                'message' => 'Cycle désactivé avec succès',
                'data' => $deactivatedCycle,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Erreur lors de la désactivation du cycle',
                'error' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Reorder cycles
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function reorder(Request $request): JsonResponse
    {
        $request->validate([
            'orders' => 'required|array',
            'orders.*' => 'required|integer',
        ]);

        try {
            $this->cycleService->reorderCycles($request->input('orders'));

            return response()->json([
                'message' => 'Cycles réorganisés avec succès',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Erreur lors de la réorganisation des cycles',
                'error' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Get cycle statistics
     *
     * @param int $id
     * @return JsonResponse
     */
    public function statistics(int $id): JsonResponse
    {
        try {
            $cycle = Cycle::findOrFail($id);
            $statistics = $this->cycleService->getCycleStatistics($cycle);

            return response()->json([
                'data' => $statistics,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Erreur lors de la récupération des statistiques',
                'error' => $e->getMessage(),
            ], 400);
        }
    }
}
