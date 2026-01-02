<?php

namespace Modules\Attendance\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Attendance\Entities\Attendance;
use Modules\Attendance\Entities\AbsenceJustification;
use Modules\Student\Entities\Student;
use Modules\Academic\Entities\AcademicYear;
use Carbon\Carbon;

class AttendanceSeeder extends Seeder
{
    public function run(): void
    {
        $this->command->info('📊 Seeding Attendance Module...');

        $academicYear = AcademicYear::where('is_current', true)->first();
        $students = Student::where('status', 'active')
                          ->with('currentEnrollment')
                          ->get();

        if (!$academicYear || $students->isEmpty()) {
            $this->command->warn('⚠️  No students or academic year found!');
            return;
        }

        // Generate attendance for last 30 days
        $endDate = now();
        $startDate = now()->subDays(30);
        $attendanceCount = 0;

        for ($date = $startDate; $date->lte($endDate); $date->addDay()) {
            // Skip weekends
            if ($date->isWeekend()) continue;

            foreach ($students as $student) {
                if (!$student->currentEnrollment) continue;

                // 85% présent, 10% retard, 5% absent
                $rand = rand(1, 100);
                
                if ($rand <= 85) {
                    $status = 'present';
                    $checkInTime = '08:00';
                } elseif ($rand <= 95) {
                    $status = 'late';
                    $checkInTime = '08:' . rand(15, 45);
                } else {
                    $status = 'absent';
                    $checkInTime = null;
                }

                $attendance = Attendance::create([
                    'student_id' => $student->id,
                    'class_room_id' => $student->currentEnrollment->class_room_id,
                    'academic_year_id' => $academicYear->id,
                    'date' => $date->copy(),
                    'status' => $status,
                    'check_in_time' => $checkInTime,
                    'expected_time' => '08:00',
                    'late_minutes' => $status === 'late' ? rand(15, 45) : 0,
                ]);

                // 70% des absences ont une justification
                if ($status === 'absent' && rand(1, 100) <= 70) {
                    $justification = AbsenceJustification::create([
                        'attendance_id' => $attendance->id,
                        'type' => ['medical', 'family', 'official'][array_rand(['medical', 'family', 'official'])],
                        'reason' => 'Raison aléatoire générée par le seeder',
                        'submitted_date' => $date->copy(),
                        'status' => ['pending', 'approved', 'rejected'][array_rand(['pending', 'approved', 'rejected'])],
                    ]);

                    // If approved, mark attendance as excused
                    if ($justification->status === 'approved') {
                        $attendance->update(['status' => 'excused']);
                    }
                }

                $attendanceCount++;
            }
        }

        $this->command->info("✓ {$attendanceCount} attendance records created for last 30 days");
        $this->command->info('🎉 Attendance Module seeded successfully!');
    }
}
