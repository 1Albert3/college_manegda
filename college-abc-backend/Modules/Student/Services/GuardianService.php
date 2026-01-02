<?php

namespace Modules\Student\Services;

use Modules\Student\Entities\Guardian;
use Modules\Student\Entities\Student;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class GuardianService
{
    public function createGuardian(int $studentId, array $data): Guardian
    {
        try {
            DB::beginTransaction();

            $student = Student::findOrFail($studentId);

            // If primary, unset others
            if ($data['is_primary'] ?? false) {
                $student->guardians()->update(['is_primary' => false]);
            }

            $guardian = $student->guardians()->create($data);

            DB::commit();
            Log::info('Guardian created', ['guardian_id' => $guardian->id]);

            return $guardian->fresh();
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to create guardian', ['error' => $e->getMessage()]);
            throw $e;
        }
    }

    public function updateGuardian(Guardian $guardian, array $data): Guardian
    {
        try {
            DB::beginTransaction();

            // If setting as primary, unset others for this student
            if (isset($data['is_primary']) && $data['is_primary']) {
                Guardian::where('student_id', $guardian->student_id)
                       ->where('id', '!=', $guardian->id)
                       ->update(['is_primary' => false]);
            }

            $guardian->update($data);

            DB::commit();
            Log::info('Guardian updated', ['guardian_id' => $guardian->id]);

            return $guardian->fresh();
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to update guardian', ['error' => $e->getMessage()]);
            throw $e;
        }
    }

    public function deleteGuardian(Guardian $guardian): bool
    {
        try {
            DB::beginTransaction();

            // Prevent deleting if it's the only guardian
            $guardianCount = Guardian::where('student_id', $guardian->student_id)->count();
            if ($guardianCount <= 1) {
                throw new \Exception('Impossible de supprimer le dernier tuteur');
            }

            // If primary, set another one as primary
            if ($guardian->is_primary) {
                $newPrimary = Guardian::where('student_id', $guardian->student_id)
                                    ->where('id', '!=', $guardian->id)
                                    ->first();
                if ($newPrimary) {
                    $newPrimary->update(['is_primary' => true]);
                }
            }

            $deleted = $guardian->delete();

            DB::commit();
            Log::info('Guardian deleted', ['guardian_id' => $guardian->id]);

            return $deleted;
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to delete guardian', ['error' => $e->getMessage()]);
            throw $e;
        }
    }

    public function setPrimary(Guardian $guardian): Guardian
    {
        return $this->updateGuardian($guardian, ['is_primary' => true]);
    }
}
