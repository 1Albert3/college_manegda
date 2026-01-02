<?php

namespace Modules\Academic\Services;

use Modules\Academic\Entities\Semester;
use Modules\Academic\Entities\AcademicYear;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class SemesterService
{
    /**
     * Create a new semester
     *
     * @param array $data
     * @return Semester
     * @throws \Exception
     */
    public function createSemester(array $data): Semester
    {
        try {
            DB::beginTransaction();

            $semester = Semester::create([
                'academic_year_id' => $data['academic_year_id'],
                'name' => $data['name'] ?? null,
                'type' => $data['type'] ?? 'trimester',
                'number' => $data['number'],
                'start_date' => $data['start_date'],
                'end_date' => $data['end_date'],
                'is_current' => $data['is_current'] ?? false,
                'description' => $data['description'] ?? null,
            ]);

            DB::commit();
            Log::info('Semester created', ['semester_id' => $semester->id]);

            return $semester->fresh();
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to create semester', ['error' => $e->getMessage()]);
            throw $e;
        }
    }

    /**
     * Update semester
     *
     * @param Semester $semester
     * @param array $data
     * @return Semester
     */
    public function updateSemester(Semester $semester, array $data): Semester
    {
        try {
            DB::beginTransaction();

            $semester->update(array_filter([
                'name' => $data['name'] ?? $semester->name,
                'type' => $data['type'] ?? $semester->type,
                'number' => $data['number'] ?? $semester->number,
                'start_date' => $data['start_date'] ?? $semester->start_date,
                'end_date' => $data['end_date'] ?? $semester->end_date,
                'is_current' => $data['is_current'] ?? $semester->is_current,
                'description' => $data['description'] ?? $semester->description,
            ]));

            DB::commit();
            Log::info('Semester updated', ['semester_id' => $semester->id]);

            return $semester->fresh();
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to update semester', ['error' => $e->getMessage()]);
            throw $e;
        }
    }

    /**
     * Generate semesters for an academic year
     *
     * @param int $academicYearId
     * @param string $type 'trimester' or 'semester'
     * @return array
     */
    public function generateSemestersForYear(int $academicYearId, string $type = 'trimester'): array
    {
        $academicYear = AcademicYear::findOrFail($academicYearId);
        
        $startDate = Carbon::parse($academicYear->start_date);
        $endDate = Carbon::parse($academicYear->end_date);

        if ($type === 'trimester') {
            return $this->generateTrimesters($academicYear, $startDate, $endDate);
        } else {
            return $this->generateSemesters($academicYear, $startDate, $endDate);
        }
    }

    /**
     * Generate 3 trimesters
     *
     * @param AcademicYear $academicYear
     * @param Carbon $startDate
     * @param Carbon $endDate
     * @return array
     */
    protected function generateTrimesters(AcademicYear $academicYear, Carbon $startDate, Carbon $endDate): array
    {
        try {
            DB::beginTransaction();

            $totalWeeks = $startDate->diffInWeeks($endDate);
            $trimesterLength = ceil($totalWeeks / 3);

            $semesters = [];

            for ($i = 1; $i <= 3; $i++) {
                $trimesterStart = $i === 1 
                    ? $startDate->copy() 
                    : $startDate->copy()->addWeeks($trimesterLength * ($i - 1));
                
                $trimesterEnd = $i === 3 
                    ? $endDate->copy() 
                    : $startDate->copy()->addWeeks($trimesterLength * $i)->subDay();

                $semester = Semester::create([
                    'academic_year_id' => $academicYear->id,
                    'name' => "Trimestre {$i}",
                    'type' => 'trimester',
                    'number' => $i,
                    'start_date' => $trimesterStart,
                    'end_date' => $trimesterEnd,
                    'is_current' => $i === 1,
                ]);

                $semesters[] = $semester;
            }

            DB::commit();
            Log::info('Trimesters generated', ['academic_year_id' => $academicYear->id, 'count' => 3]);

            return $semesters;
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to generate trimesters', ['error' => $e->getMessage()]);
            throw $e;
        }
    }

    /**
     * Generate 2 semesters
     *
     * @param AcademicYear $academicYear
     * @param Carbon $startDate
     * @param Carbon $endDate
     * @return array
     */
    protected function generateSemesters(AcademicYear $academicYear, Carbon $startDate, Carbon $endDate): array
    {
        try {
            DB::beginTransaction();

            $totalWeeks = $startDate->diffInWeeks($endDate);
            $semesterLength = ceil($totalWeeks / 2);

            $semesters = [];

            for ($i = 1; $i <= 2; $i++) {
                $semesterStart = $i === 1 
                    ? $startDate->copy() 
                    : $startDate->copy()->addWeeks($semesterLength);
                
                $semesterEnd = $i === 2 
                    ? $endDate->copy() 
                    : $startDate->copy()->addWeeks($semesterLength)->subDay();

                $semester = Semester::create([
                    'academic_year_id' => $academicYear->id,
                    'name' => "Semestre {$i}",
                    'type' => 'semester',
                    'number' => $i,
                    'start_date' => $semesterStart,
                    'end_date' => $semesterEnd,
                    'is_current' => $i === 1,
                ]);

                $semesters[] = $semester;
            }

            DB::commit();
            Log::info('Semesters generated', ['academic_year_id' => $academicYear->id, 'count' => 2]);

            return $semesters;
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to generate semesters', ['error' => $e->getMessage()]);
            throw $e;
        }
    }

    /**
     * Set semester as current
     *
     * @param Semester $semester
     * @return Semester
     */
    public function setCurrentSemester(Semester $semester): Semester
    {
        try {
            DB::beginTransaction();

            $semester->setAsCurrent();

            DB::commit();
            Log::info('Semester set as current', ['semester_id' => $semester->id]);

            return $semester->fresh();
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to set current semester', ['error' => $e->getMessage()]);
            throw $e;
        }
    }

    /**
     * Get current semester
     *
     * @return Semester|null
     */
    public function getCurrentSemester(): ?Semester
    {
        return Semester::getCurrentSemester();
    }

    /**
     * Get ongoing semester
     *
     * @return Semester|null
     */
    public function getOngoingSemester(): ?Semester
    {
        return Semester::getOngoingSemester();
    }

    /**
     * Get semesters by academic year
     *
     * @param int $academicYearId
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getSemestersByYear(int $academicYearId)
    {
        return Semester::byAcademicYear($academicYearId)
                      ->ordered()
                      ->get();
    }

    /**
     * Delete semester
     *
     * @param Semester $semester
     * @return bool
     * @throws \Exception
     */
    public function deleteSemester(Semester $semester): bool
    {
        // Check if semester has grades
        if ($semester->grades()->exists()) {
            throw new \Exception('Impossible de supprimer un semestre qui contient des notes.');
        }

        try {
            DB::beginTransaction();

            $deleted = $semester->delete();

            DB::commit();
            Log::info('Semester deleted', ['semester_id' => $semester->id]);

            return $deleted;
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to delete semester', ['error' => $e->getMessage()]);
            throw $e;
        }
    }
}
