<?php

namespace Modules\Academic\Services;

use Modules\Academic\Entities\Cycle;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class CycleService
{
    /**
     * Create a new cycle
     *
     * @param array $data
     * @return Cycle
     */
    public function createCycle(array $data): Cycle
    {
        try {
            DB::beginTransaction();

            $cycle = Cycle::create([
                'name' => $data['name'],
                'slug' => $data['slug'] ?? null, // Auto-generated in boot
                'description' => $data['description'] ?? null,
                'order' => $data['order'] ?? null, // Auto-generated in boot
                'is_active' => $data['is_active'] ?? true,
            ]);

            DB::commit();
            Log::info('Cycle created', ['cycle_id' => $cycle->id]);

            return $cycle->fresh();
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to create cycle', ['error' => $e->getMessage()]);
            throw $e;
        }
    }

    /**
     * Update cycle
     *
     * @param Cycle $cycle
     * @param array $data
     * @return Cycle
     */
    public function updateCycle(Cycle $cycle, array $data): Cycle
    {
        try {
            DB::beginTransaction();

            $cycle->update(array_filter([
                'name' => $data['name'] ?? $cycle->name,
                'slug' => $data['slug'] ?? $cycle->slug,
                'description' => $data['description'] ?? $cycle->description,
                'order' => $data['order'] ?? $cycle->order,
                'is_active' => $data['is_active'] ?? $cycle->is_active,
            ]));

            DB::commit();
            Log::info('Cycle updated', ['cycle_id' => $cycle->id]);

            return $cycle->fresh();
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to update cycle', ['error' => $e->getMessage()]);
            throw $e;
        }
    }

    /**
     * Delete cycle
     *
     * @param Cycle $cycle
     * @return bool
     * @throws \Exception
     */
    public function deleteCycle(Cycle $cycle): bool
    {
        // Check if cycle has levels
        if ($cycle->levels()->exists()) {
            throw new \Exception('Impossible de supprimer un cycle qui contient des niveaux.');
        }

        // Check if cycle has fee types
        if ($cycle->feeTypes()->exists()) {
            throw new \Exception('Impossible de supprimer un cycle qui contient des types de frais.');
        }

        try {
            DB::beginTransaction();

            $deleted = $cycle->delete();

            DB::commit();
            Log::info('Cycle deleted', ['cycle_id' => $cycle->id]);

            return $deleted;
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to delete cycle', ['error' => $e->getMessage()]);
            throw $e;
        }
    }

    /**
     * Get all cycles with levels
     *
     * @param bool $activeOnly
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getAllCyclesWithLevels(bool $activeOnly = false)
    {
        $query = Cycle::with(['levels' => function($q) use ($activeOnly) {
            if ($activeOnly) {
                $q->active();
            }
            $q->ordered();
        }])->ordered();

        if ($activeOnly) {
            $query->active();
        }

        return $query->get();
    }

    /**
     * Activate cycle
     *
     * @param Cycle $cycle
     * @return Cycle
     */
    public function activateCycle(Cycle $cycle): Cycle
    {
        $cycle->activate();
        Log::info('Cycle activated', ['cycle_id' => $cycle->id]);

        return $cycle->fresh();
    }

    /**
     * Deactivate cycle
     *
     * @param Cycle $cycle
     * @return Cycle
     */
    public function deactivateCycle(Cycle $cycle): Cycle
    {
        $cycle->deactivate();
        Log::info('Cycle deactivated', ['cycle_id' => $cycle->id]);

        return $cycle->fresh();
    }

    /**
     * Reorder cycles
     *
     * @param array $orders Array of ['id' => order]
     * @return bool
     */
    public function reorderCycles(array $orders): bool
    {
        try {
            DB::beginTransaction();

            foreach ($orders as $id => $order) {
                Cycle::where('id', $id)->update(['order' => $order]);
            }

            DB::commit();
            Log::info('Cycles reordered', ['cycles_count' => count($orders)]);

            return true;
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to reorder cycles', ['error' => $e->getMessage()]);
            throw $e;
        }
    }

    /**
     * Get cycle statistics
     *
     * @param Cycle $cycle
     * @return array
     */
    public function getCycleStatistics(Cycle $cycle): array
    {
        return [
            'levels_count' => $cycle->levels_count,
            'class_rooms_count' => $cycle->class_rooms_count,
            'fee_types_count' => $cycle->feeTypes()->count(),
            'is_active' => $cycle->is_active,
        ];
    }
}
