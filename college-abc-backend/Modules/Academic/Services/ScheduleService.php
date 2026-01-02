<?php

namespace Modules\Academic\Services;

use Modules\Academic\Entities\Schedule;
use Modules\Academic\Entities\ClassRoom;
use Modules\Academic\Entities\Subject;
use Modules\Academic\Entities\AcademicYear;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ScheduleService
{
    /**
     * Create a new schedule entry
     *
     * @param array $data
     * @return Schedule
     * @throws \Exception
     */
    public function createSchedule(array $data): Schedule
    {
        // Validate no conflict before creating
        $this->validateNoConflict($data);

        try {
            DB::beginTransaction();

            $schedule = Schedule::create([
                'class_room_id' => $data['class_room_id'],
                'subject_id' => $data['subject_id'],
                'teacher_id' => $data['teacher_id'],
                'academic_year_id' => $data['academic_year_id'] ?? AcademicYear::getCurrentYear()?->id,
                'day_of_week' => $data['day_of_week'],
                'start_time' => $data['start_time'],
                'end_time' => $data['end_time'],
                'room' => $data['room'] ?? null,
                'notes' => $data['notes'] ?? null,
            ]);

            DB::commit();
            Log::info('Schedule created', ['schedule_id' => $schedule->id]);

            return $schedule->fresh(['classRoom', 'subject', 'teacher']);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to create schedule', ['error' => $e->getMessage()]);
            throw $e;
        }
    }

    /**
     * Update schedule
     *
     * @param Schedule $schedule
     * @param array $data
     * @return Schedule
     * @throws \Exception
     */
    public function updateSchedule(Schedule $schedule, array $data): Schedule
    {
        // Validate no conflict with updated data
        $mergedData = array_merge([
            'class_room_id' => $schedule->class_room_id,
            'teacher_id' => $schedule->teacher_id,
            'day_of_week' => $schedule->day_of_week,
            'start_time' => $schedule->start_time,
            'end_time' => $schedule->end_time,
            'academic_year_id' => $schedule->academic_year_id,
        ], $data);
        $mergedData['exclude_id'] = $schedule->id;

        $this->validateNoConflict($mergedData);

        try {
            DB::beginTransaction();

            $schedule->update(array_filter([
                'class_room_id' => $data['class_room_id'] ?? $schedule->class_room_id,
                'subject_id' => $data['subject_id'] ?? $schedule->subject_id,
                'teacher_id' => $data['teacher_id'] ?? $schedule->teacher_id,
                'day_of_week' => $data['day_of_week'] ?? $schedule->day_of_week,
                'start_time' => $data['start_time'] ?? $schedule->start_time,
                'end_time' => $data['end_time'] ?? $schedule->end_time,
                'room' => $data['room'] ?? $schedule->room,
                'notes' => $data['notes'] ?? $schedule->notes,
            ]));

            DB::commit();
            Log::info('Schedule updated', ['schedule_id' => $schedule->id]);

            return $schedule->fresh(['classRoom', 'subject', 'teacher']);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to update schedule', ['error' => $e->getMessage()]);
            throw $e;
        }
    }

    /**
     * Delete schedule
     *
     * @param Schedule $schedule
     * @return bool
     */
    public function deleteSchedule(Schedule $schedule): bool
    {
        try {
            DB::beginTransaction();

            $deleted = $schedule->delete();

            DB::commit();
            Log::info('Schedule deleted', ['schedule_id' => $schedule->id]);

            return $deleted;
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to delete schedule', ['error' => $e->getMessage()]);
            throw $e;
        }
    }

    /**
     * Get class schedule
     *
     * @param int $classRoomId
     * @param int|null $academicYearId
     * @return array
     */
    public function getClassSchedule(int $classRoomId, ?int $academicYearId = null): array
    {
        return Schedule::getClassSchedule($classRoomId, $academicYearId)->toArray();
    }

    /**
     * Get teacher schedule
     *
     * @param int $teacherId
     * @param int|null $academicYearId
     * @return array
     */
    public function getTeacherSchedule(int $teacherId, ?int $academicYearId = null): array
    {
        return Schedule::getTeacherSchedule($teacherId, $academicYearId)->toArray();
    }

    /**
     * Get today's schedule for a class
     *
     * @param int $classRoomId
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getTodayClassSchedule(int $classRoomId)
    {
        return Schedule::byClass($classRoomId)
                      ->today()
                      ->with(['subject', 'teacher'])
                      ->ordered()
                      ->get();
    }

    /**
     * Get today's schedule for a teacher
     *
     * @param int $teacherId
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getTodayTeacherSchedule(int $teacherId)
    {
        return Schedule::byTeacher($teacherId)
                      ->today()
                      ->with(['subject', 'classRoom'])
                      ->ordered()
                      ->get();
    }

    /**
     * Bulk create schedules for a class
     *
     * @param int $classRoomId
     * @param array $schedules Array of schedule data
     * @return array
     * @throws \Exception
     */
    public function bulkCreateForClass(int $classRoomId, array $schedules): array
    {
        $created = [];
        $errors = [];

        DB::beginTransaction();

        try {
            foreach ($schedules as $scheduleData) {
                try {
                    $scheduleData['class_room_id'] = $classRoomId;
                    $created[] = $this->createSchedule($scheduleData);
                } catch (\Exception $e) {
                    $errors[] = [
                        'data' => $scheduleData,
                        'error' => $e->getMessage(),
                    ];
                }
            }

            if (count($errors) > 0) {
                DB::rollBack();
                throw new \Exception('Certaines entrées n\'ont pu être créées : ' . json_encode($errors));
            }

            DB::commit();
            Log::info('Bulk schedules created', ['class_room_id' => $classRoomId, 'count' => count($created)]);

            return $created;
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to bulk create schedules', ['error' => $e->getMessage()]);
            throw $e;
        }
    }

    /**
     * Copy schedule from one year to another
     *
     * @param int $fromYearId
     * @param int $toYearId
     * @param int|null $classRoomId Optional: copy only specific class
     * @return int Number of schedules copied
     * @throws \Exception
     */
    public function copyScheduleToNewYear(int $fromYearId, int $toYearId, ?int $classRoomId = null): int
    {
        try {
            DB::beginTransaction();

            $query = Schedule::byAcademicYear($fromYearId);
            
            if ($classRoomId) {
                $query->byClass($classRoomId);
            }

            $oldSchedules = $query->get();
            $copied = 0;

            foreach ($oldSchedules as $oldSchedule) {
                Schedule::create([
                    'class_room_id' => $oldSchedule->class_room_id,
                    'subject_id' => $oldSchedule->subject_id,
                    'teacher_id' => $oldSchedule->teacher_id,
                    'academic_year_id' => $toYearId,
                    'day_of_week' => $oldSchedule->day_of_week,
                    'start_time' => $oldSchedule->start_time,
                    'end_time' => $oldSchedule->end_time,
                    'room' => $oldSchedule->room,
                    'notes' => $oldSchedule->notes,
                ]);

                $copied++;
            }

            DB::commit();
            Log::info('Schedules copied to new year', [
                'from_year_id' => $fromYearId,
                'to_year_id' => $toYearId,
                'count' => $copied,
            ]);

            return $copied;
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to copy schedules', ['error' => $e->getMessage()]);
            throw $e;
        }
    }

    /**
     * Validate no schedule conflict
     *
     * @param array $data
     * @return void
     * @throws \Exception
     */
    protected function validateNoConflict(array $data): void
    {
        $excludeId = $data['exclude_id'] ?? null;

        // Check teacher conflict
        $teacherConflict = Schedule::where('teacher_id', $data['teacher_id'])
            ->where('day_of_week', $data['day_of_week'])
            ->where('academic_year_id', $data['academic_year_id'])
            ->when($excludeId, fn($q) => $q->where('id', '!=', $excludeId))
            ->where(function($query) use ($data) {
                $query->whereBetween('start_time', [$data['start_time'], $data['end_time']])
                      ->orWhereBetween('end_time', [$data['start_time'], $data['end_time']])
                      ->orWhere(function($q) use ($data) {
                          $q->where('start_time', '<=', $data['start_time'])
                            ->where('end_time', '>=', $data['end_time']);
                      });
            })
            ->exists();

        if ($teacherConflict) {
            throw new \Exception('Le professeur a déjà un cours prévu à cette heure.');
        }

        // Check class conflict
        $classConflict = Schedule::where('class_room_id', $data['class_room_id'])
            ->where('day_of_week', $data['day_of_week'])
            ->where('academic_year_id', $data['academic_year_id'])
            ->when($excludeId, fn($q) => $q->where('id', '!=', $excludeId))
            ->where(function($query) use ($data) {
                $query->whereBetween('start_time', [$data['start_time'], $data['end_time']])
                      ->orWhereBetween('end_time', [$data['start_time'], $data['end_time']])
                      ->orWhere(function($q) use ($data) {
                          $q->where('start_time', '<=', $data['start_time'])
                            ->where('end_time', '>=', $data['end_time']);
                      });
            })
            ->exists();

        if ($classConflict) {
            throw new \Exception('La classe a déjà un cours prévu à cette heure.');
        }
    }

    /**
     * Get statistics for schedules
     *
     * @param int|null $academicYearId
     * @return array
     */
    public function getStatistics(?int $academicYearId = null): array
    {
        $academicYearId = $academicYearId ?? AcademicYear::getCurrentYear()?->id;

        $query = Schedule::byAcademicYear($academicYearId);

        return [
            'total_schedules' => $query->count(),
            'by_day' => $query->select('day_of_week', DB::raw('count(*) as count'))
                             ->groupBy('day_of_week')
                             ->pluck('count', 'day_of_week')
                             ->toArray(),
            'by_subject' => $query->with('subject')
                                 ->get()
                                 ->groupBy('subject.name')
                                 ->map->count()
                                 ->toArray(),
            'teachers_count' => $query->distinct('teacher_id')->count('teacher_id'),
            'classes_count' => $query->distinct('class_room_id')->count('class_room_id'),
        ];
    }
}
