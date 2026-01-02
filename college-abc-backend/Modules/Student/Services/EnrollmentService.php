<?php

namespace Modules\Student\Services;

use Modules\Student\Entities\Enrollment;
use Modules\Student\Entities\Student;
use Modules\Academic\Entities\AcademicYear;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class EnrollmentService
{
    public function enrollStudent(array $data): Enrollment
    {
        try {
            DB::beginTransaction();

            // Check for duplicate enrollment
            $exists = Enrollment::where('student_id', $data['student_id'])
                               ->where('academic_year_id', $data['academic_year_id'])
                               ->where('class_room_id', $data['class_room_id'])
                               ->exists();

            if ($exists) {
                throw new \Exception('Cet élève est déjà inscrit dans cette classe pour cette année');
            }

            $enrollment = Enrollment::create([
                'student_id' => $data['student_id'],
                'class_room_id' => $data['class_room_id'],
                'academic_year_id' => $data['academic_year_id'] ?? AcademicYear::getCurrentYear()?->id,
                'enrollment_date' => $data['enrollment_date'] ?? now(),
                'status' => $data['status'] ?? 'REGISTERED',
                'discount_percentage' => $data['discount_percentage'] ?? 0,
                'notes' => $data['notes'] ?? null,
            ]);

            DB::commit();
            Log::info('Student enrolled', ['enrollment_id' => $enrollment->id]);

            return $enrollment->fresh(['student', 'classRoom', 'academicYear']);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to enroll student', ['error' => $e->getMessage()]);
            throw $e;
        }
    }

    public function updateEnrollment(Enrollment $enrollment, array $data): Enrollment
    {
        try {
            DB::beginTransaction();

            $enrollment->update($data);

            DB::commit();
            Log::info('Enrollment updated', ['enrollment_id' => $enrollment->id]);

            return $enrollment->fresh();
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to update enrollment', ['error' => $e->getMessage()]);
            throw $e;
        }
    }

    public function changeStatus(Enrollment $enrollment, string $status): Enrollment
    {
        $validStatuses = ['REGISTERED', 'ACTIVE', 'LEFT', 'GRADUATED'];
        
        if (!in_array($status, $validStatuses)) {
            throw new \Exception('Statut invalide');
        }

        return $this->updateEnrollment($enrollment, ['status' => $status]);
    }

    public function transferStudent(Enrollment $enrollment, int $newClassRoomId): Enrollment
    {
        try {
            DB::beginTransaction();

            // Create new enrollment
            $newEnrollment = $this->enrollStudent([
                'student_id' => $enrollment->student_id,
                'class_room_id' => $newClassRoomId,
                'academic_year_id' => $enrollment->academic_year_id,
                'enrollment_date' => now(),
                'status' => 'ACTIVE',
                'discount_percentage' => $enrollment->discount_percentage,
                'notes' => "Transféré depuis {$enrollment->classRoom->name}",
            ]);

            // Mark old enrollment as LEFT
            $enrollment->update(['status' => 'LEFT']);

            DB::commit();
            Log::info('Student transferred', [
                'old_enrollment_id' => $enrollment->id,
                'new_enrollment_id' => $newEnrollment->id
            ]);

            return $newEnrollment;
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to transfer student', ['error' => $e->getMessage()]);
            throw $e;
        }
    }

    public function getEnrollmentsByClass(int $classRoomId, ?int $academicYearId = null)
    {
        $query = Enrollment::where('class_room_id', $classRoomId)
                          ->with(['student', 'academicYear']);

        if ($academicYearId) {
            $query->where('academic_year_id', $academicYearId);
        }

        return $query->get();
    }

    public function getActiveEnrollments()
    {
        return Enrollment::where('status', 'ACTIVE')
                        ->with(['student', 'classRoom', 'academicYear'])
                        ->get();
    }
}
