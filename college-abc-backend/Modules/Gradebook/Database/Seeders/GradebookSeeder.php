<?php

namespace Modules\Gradebook\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Gradebook\Entities\Evaluation;
use Modules\Gradebook\Entities\Grade;
use Modules\Academic\Entities\ClassRoom;
use Modules\Academic\Entities\AcademicYear;
use Modules\Academic\Entities\Semester;
use Modules\Student\Entities\Student;

class GradebookSeeder extends Seeder
{
    public function run(): void
    {
        $this->command->info('📝 Seeding Gradebook Module...');

        $academicYear = AcademicYear::where('is_current', true)->first();
        $semesters = Semester::where('academic_year_id', $academicYear?->id)->get();
        $classRooms = ClassRoom::with(['subjects', 'students'])->where('is_active', true)->get();

        if ($classRooms->isEmpty() || $semesters->isEmpty()) {
            $this->command->warn('⚠️  Missing data! Run Academic and Student seeders first.');
            return;
        }

        $defaultTeacher = \Modules\Core\Entities\User::first();

        $evaluationCount = 0;
        $gradeCount = 0;

        foreach ($classRooms as $classRoom) {
            $subjects = $classRoom->subjects;
            $students = $classRoom->students()->get();

            foreach ($subjects->take(5) as $subject) { // 5 matières par classe
                foreach ($semesters as $semester) {
                    // 2-3 évaluations par matière par trimestre
                    for ($i = 1; $i <= rand(2, 3); $i++) {
                        $evaluation = Evaluation::create([
                            'subject_id' => $subject->id,
                            'class_room_id' => $classRoom->id,
                            'teacher_id' => $classRoom->main_teacher_id ?? $defaultTeacher->id,
                            'semester_id' => $semester->id,
                            'academic_year_id' => $academicYear->id,
                            'title' => ['Examen', 'Interrogation', 'Devoir'][$i - 1] . " {$i}",
                            'type' => ['exam', 'test', 'homework'][rand(0, 2)],
                            'max_score' => 20,
                            'coefficient' => [1, 1.5, 2][rand(0, 2)],
                            'date' => $semester->start_date->addDays(rand(1, 30)),
                        ]);

                        $evaluationCount++;

                        // Générer notes pour chaque élève
                        foreach ($students as $student) {
                            Grade::create([
                                'evaluation_id' => $evaluation->id,
                                'student_id' => $student->id,
                                'score' => rand(5, 20), // Notes aléatoires 5-20
                                'graded_by' => $defaultTeacher->id,
                            ]);
                            $gradeCount++;
                        }
                    }
                }
            }
        }

        $this->command->info("✓ {$evaluationCount} evaluations created");
        $this->command->info("✓ {$gradeCount} grades recorded");
        $this->command->info('🎉 Gradebook Module seeded successfully!');
    }
}
