<?php

namespace Modules\Academic\Services;

use Modules\Academic\Entities\Level;
use Modules\Academic\Entities\Cycle;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class LevelService
{
    /**
     * Create a new level
     *
     * @param array $data
     * @return Level
     */
    public function createLevel(array $data): Level
    {
        try {
            DB::beginTransaction();

            $level = Level::create([
                'cycle_id' => $data['cycle_id'],
                'name' => $data['name'],
                'code' => $data['code'] ?? null, // Auto-generated in boot
                'description' => $data['description'] ?? null,
                'order' => $data['order'] ?? null, // Auto-generated in boot
                'is_active' => $data['is_active'] ?? true,
            ]);

            DB::commit();
            Log::info('Level created', ['level_id' => $level->id]);

            return $level->fresh(['cycle']);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to create level', ['error' => $e->getMessage()]);
            throw $e;
        }
    }

    /**
     * Update level
     *
     * @param Level $level
     * @param array $data
     * @return Level
     */
    public function updateLevel(Level $level, array $data): Level
    {
        try {
            DB::beginTransaction();

            $level->update(array_filter([
                'cycle_id' => $data['cycle_id'] ?? $level->cycle_id,
                'name' => $data['name'] ?? $level->name,
                'code' => $data['code'] ?? $level->code,
                'description' => $data['description'] ?? $level->description,
                'order' => $data['order'] ?? $level->order,
                'is_active' => $data['is_active'] ?? $level->is_active,
            ]));

            DB::commit();
            Log::info('Level updated', ['level_id' => $level->id]);

            return $level->fresh(['cycle']);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to update level', ['error' => $e->getMessage()]);
            throw $e;
        }
    }

    /**
     * Delete level
     *
     * @param Level $level
     * @return bool
     * @throws \Exception
     */
    public function deleteLevel(Level $level): bool
    {
        // Check if level has classrooms
        if ($level->classRooms()->exists()) {
            throw new \Exception('Impossible de supprimer un niveau qui contient des classes.');
        }

        // Check if level has fee types
        if ($level->feeTypes()->exists()) {
            throw new \Exception('Impossible de supprimer un niveau qui contient des types de frais.');
        }

        try {
            DB::beginTransaction();

            $deleted = $level->delete();

            DB::commit();
            Log::info('Level deleted', ['level_id' => $level->id]);

            return $deleted;
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to delete level', ['error' => $e->getMessage()]);
            throw $e;
        }
    }

    /**
     * Get levels by cycle
     *
     * @param int $cycleId
     * @param bool $activeOnly
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getLevelsByCycle(int $cycleId, bool $activeOnly = false)
    {
        $query = Level::byCycle($cycleId)->ordered();

        if ($activeOnly) {
            $query->active();
        }

        return $query->get();
    }

    /**
     * Get all levels with classrooms
     *
     * @param bool $activeOnly
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getAllLevelsWithClassrooms(bool $activeOnly = false)
    {
        $query = Level::with(['cycle', 'classRooms' => function($q) use ($activeOnly) {
            if ($activeOnly) {
                $q->active();
            }
        }])->ordered();

        if ($activeOnly) {
            $query->active();
        }

        return $query->get();
    }

    /**
     * Activate level
     *
     * @param Level $level
     * @return Level
     */
    public function activateLevel(Level $level): Level
    {
        $level->activate();
        Log::info('Level activated', ['level_id' => $level->id]);

        return $level->fresh();
    }

    /**
     * Deactivate level
     *
     * @param Level $level
     * @return Level
     */
    public function deactivateLevel(Level $level): Level
    {
        $level->deactivate();
        Log::info('Level deactivated', ['level_id' => $level->id]);

        return $level->fresh();
    }

    /**
     * Reorder levels within a cycle
     *
     * @param int $cycleId
     * @param array $orders Array of ['id' => order]
     * @return bool
     */
    public function reorderLevels(int $cycleId, array $orders): bool
    {
        try {
            DB::beginTransaction();

            foreach ($orders as $id => $order) {
                Level::where('id', $id)
                    ->where('cycle_id', $cycleId)
                    ->update(['order' => $order]);
            }

            DB::commit();
            Log::info('Levels reordered', ['cycle_id' => $cycleId, 'levels_count' => count($orders)]);

            return true;
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to reorder levels', ['error' => $e->getMessage()]);
            throw $e;
        }
    }

    /**
     * Get level statistics
     *
     * @param Level $level
     * @return array
     */
    public function getLevelStatistics(Level $level): array
    {
        return [
            'class_rooms_count' => $level->class_rooms_count,
            'students_count' => $level->students_count,
            'fee_types_count' => $level->feeTypes()->count(),
            'cycle' => $level->cycle->name,
            'is_active' => $level->is_active,
        ];
    }

    /**
     * Search levels
     *
     * @param string $term
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function searchLevels(string $term)
    {
        return Level::search($term)
                   ->with('cycle')
                   ->get();
    }
}
