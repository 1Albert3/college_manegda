<?php

namespace Modules\Academic\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Academic\Entities\AcademicYear;
use Modules\Academic\Entities\Cycle;
use Modules\Academic\Entities\Level;
use Modules\Academic\Entities\Semester;
use Modules\Academic\Entities\Subject;
use Modules\Academic\Entities\ClassRoom;
use Modules\Academic\Entities\Schedule;
use Modules\Core\Entities\User;
use Carbon\Carbon;

class AcademicSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->command->info('🎓 Seeding Academic Module...');

        // 1. Create Cycles
        $cycles = $this->seedCycles();
        $this->command->info('✓ Cycles created: ' . count($cycles));

        // 2. Create Levels
        $levels = $this->seedLevels($cycles);
        $this->command->info('✓ Levels created: ' . count($levels));

        // 3. Create Academic Year
        $academicYear = $this->seedAcademicYear();
        $this->command->info('✓ Academic Year created: ' . $academicYear->name);

        // 4. Create Semesters
        $semesters = $this->seedSemesters($academicYear);
        $this->command->info('✓ Semesters created: ' . count($semesters));

        // 5. Create Subjects
        $subjects = $this->seedSubjects();
        $this->command->info('✓ Subjects created: ' . count($subjects));

        // 6. Create ClassRooms (only if we have levels)
        if (count($levels) > 0) {
            $classRooms = $this->seedClassRooms($levels, $academicYear);
            $this->command->info('✓ ClassRooms created: ' . count($classRooms));

            // 7. Attach subjects to classes
            $this->attachSubjectsToClasses($classRooms, $subjects, $academicYear);
            $this->command->info('✓ Subjects attached to classes');

            // 8. Create sample schedules (if we have teachers)
            if (User::where('role', 'teacher')->exists()) {
                $this->seedSchedules($classRooms, $subjects, $academicYear);
                $this->command->info('✓ Sample schedules created');
            }
        }

        $this->command->info('🎉 Academic Module seeded successfully!');
    }

    /**
     * Seed Cycles (Primaire, Collège, Lycée)
     */
    protected function seedCycles(): array
    {
        $cyclesData = [
            [
                'name' => 'Primaire',
                'slug' => 'primaire',
                'description' => 'Enseignement primaire (CP1 à CM2)',
                'order' => 1,
                'is_active' => true,
            ],
            [
                'name' => 'Collège',
                'slug' => 'college',
                'description' => 'Premier cycle du secondaire (6ème à 3ème)',
                'order' => 2,
                'is_active' => true,
            ],
            [
                'name' => 'Lycée',
                'slug' => 'lycee',
                'description' => 'Second cycle du secondaire (2nde à Terminale)',
                'order' => 3,
                'is_active' => true,
            ],
        ];

        $cycles = [];
        foreach ($cyclesData as $data) {
            $cycles[] = Cycle::create($data);
        }

        return $cycles;
    }

    /**
     * Seed Levels per Cycle
     */
    protected function seedLevels(array $cycles): array
    {
        $levelsData = [
            // Primaire
            'primaire' => [
                ['name' => 'CP1', 'code' => 'CP1', 'order' => 1],
                ['name' => 'CP2', 'code' => 'CP2', 'order' => 2],
                ['name' => 'CE1', 'code' => 'CE1', 'order' => 3],
                ['name' => 'CE2', 'code' => 'CE2', 'order' => 4],
                ['name' => 'CM1', 'code' => 'CM1', 'order' => 5],
                ['name' => 'CM2', 'code' => 'CM2', 'order' => 6],
            ],
            // Collège
            'college' => [
                ['name' => '6ème', 'code' => '6EME', 'order' => 1],
                ['name' => '5ème', 'code' => '5EME', 'order' => 2],
                ['name' => '4ème', 'code' => '4EME', 'order' => 3],
                ['name' => '3ème', 'code' => '3EME', 'order' => 4],
            ],
            // Lycée
            'lycee' => [
                ['name' => '2nde', 'code' => '2NDE', 'order' => 1],
                ['name' => '1ère', 'code' => '1ERE', 'order' => 2],
                ['name' => 'Terminale', 'code' => 'TERM', 'order' => 3],
            ],
        ];

        $levels = [];
        foreach ($cycles as $cycle) {
            $cycleLevels = $levelsData[$cycle->slug] ?? [];
            
            foreach ($cycleLevels as $levelData) {
                $levels[] = Level::create([
                    'cycle_id' => $cycle->id,
                    'name' => $levelData['name'],
                    'code' => $levelData['code'],
                    'description' => "Niveau {$levelData['name']} du cycle {$cycle->name}",
                    'order' => $levelData['order'],
                    'is_active' => true,
                ]);
            }
        }

        return $levels;
    }

    /**
     * Seed Academic Year
     */
    protected function seedAcademicYear(): AcademicYear
    {
        return AcademicYear::create([
            'name' => '2024-2025',
            'start_date' => Carbon::create(2024, 9, 1),
            'end_date' => Carbon::create(2025, 6, 30),
            'is_current' => true,
            'description' => 'Année académique 2024-2025',
        ]);
    }

    /**
     * Seed Semesters (Trimesters for this example)
     */
    protected function seedSemesters(AcademicYear $academicYear): array
    {
        $semestersData = [
            [
                'name' => 'Trimestre 1',
                'type' => 'trimester',
                'number' => 1,
                'start_date' => Carbon::create(2024, 9, 1),
                'end_date' => Carbon::create(2024, 12, 15),
                'is_current' => true,
            ],
            [
                'name' => 'Trimestre 2',
                'type' => 'trimester',
                'number' => 2,
                'start_date' => Carbon::create(2025, 1, 6),
                'end_date' => Carbon::create(2025, 3, 31),
                'is_current' => false,
            ],
            [
                'name' => 'Trimestre 3',
                'type' => 'trimester',
                'number' => 3,
                'start_date' => Carbon::create(2025, 4, 1),
                'end_date' => Carbon::create(2025, 6, 30),
                'is_current' => false,
            ],
        ];

        $semesters = [];
        foreach ($semestersData as $data) {
            $data['academic_year_id'] = $academicYear->id;
            $semesters[] = Semester::create($data);
        }

        return $semesters;
    }

    /**
     * Seed Subjects
     */
    protected function seedSubjects(): array
    {
        $subjectsData = [
            // Core subjects
            ['name' => 'Mathématiques', 'code' => 'MATH', 'coefficient' => 3, 'color' => '#3B82F6'],
            ['name' => 'Français', 'code' => 'FR', 'coefficient' => 3, 'color' => '#EF4444'],
            ['name' => 'Anglais', 'code' => 'ANG', 'coefficient' => 2, 'color' => '#10B981'],
            ['name' => 'Histoire-Géographie', 'code' => 'HIST-GEO', 'coefficient' => 2, 'color' => '#F59E0B'],
            ['name' => 'Sciences Physiques', 'code' => 'PC', 'coefficient' => 2, 'color' => '#8B5CF6'],
            ['name' => 'Sciences de la Vie et de la Terre', 'code' => 'SVT', 'coefficient' => 2, 'color' => '#06B6D4'],
            ['name' => 'Éducation Physique et Sportive', 'code' => 'EPS', 'coefficient' => 1, 'color' => '#EC4899'],
            ['name' => 'Arts Plastiques', 'code' => 'ARTS', 'coefficient' => 1, 'color' => '#F97316'],
            ['name' => 'Musique', 'code' => 'MUS', 'coefficient' => 1, 'color' => '#84CC16'],
            ['name' => 'Philosophie', 'code' => 'PHILO', 'coefficient' => 3, 'color' => '#6366F1'],
            ['name' => 'Économie', 'code' => 'ECO', 'coefficient' => 2, 'color' => '#14B8A6'],
            ['name' => 'Informatique', 'code' => 'INFO', 'coefficient' => 2, 'color' => '#8B5CF6'],
        ];

        $subjects = [];
        foreach ($subjectsData as $data) {
            $subjects[] = Subject::create([
                'name' => $data['name'],
                'code' => $data['code'],
                'coefficient' => $data['coefficient'],
                'description' => "Matière {$data['name']}",
                'color' => $data['color'],
                'is_active' => true,
            ]);
        }

        return $subjects;
    }

    /**
     * Seed ClassRooms
     */
    protected function seedClassRooms(array $levels, AcademicYear $academicYear): array
    {
        $classRooms = [];

        foreach ($levels as $level) {
            $numberOfClasses = in_array($level->code, ['6EME', '3EME', '2NDE', 'TERM']) ? 2 : 1;

            for ($i = 1; $i <= $numberOfClasses; $i++) {
                $className = $numberOfClasses > 1 
                    ? "{$level->name} {$i}" 
                    : $level->name;

                $classRooms[] = ClassRoom::create([
                    'level_id' => $level->id,
                    'academic_year_id' => $academicYear->id,
                    'name' => $className,
                    'room_number' => "S{$level->order}-{$i}",
                    'capacity' => 40,
                    'description' => "Classe de {$className}",
                    'is_active' => true,
                ]);
            }
        }

        return $classRooms;
    }

    /**
     * Attach subjects to classes
     */
    protected function attachSubjectsToClasses(array $classRooms, array $subjects, AcademicYear $academicYear): void
    {
        foreach ($classRooms as $classRoom) {
            // Load the level relationship
            $classRoom->load('level');
            
            $levelSubjects = $this->getSubjectsForLevel($classRoom->level->code, $subjects);

            foreach ($levelSubjects as $subject) {
                $classRoom->subjects()->attach($subject->id, [
                    'hours_per_week' => rand(2, 5),
                    'coefficient' => $subject->coefficient,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }
    }

    /**
     * Get subjects appropriate for a level
     */
    protected function getSubjectsForLevel(string $levelCode, array $subjects): array
    {
        $subjectCodes = [];

        if (in_array($levelCode, ['CP1', 'CP2', 'CE1', 'CE2', 'CM1', 'CM2'])) {
            $subjectCodes = ['MATH', 'FR', 'ANG', 'HIST-GEO', 'EPS', 'ARTS', 'MUS'];
        }
        elseif (in_array($levelCode, ['6EME', '5EME', '4EME', '3EME'])) {
            $subjectCodes = ['MATH', 'FR', 'ANG', 'HIST-GEO', 'PC', 'SVT', 'EPS', 'ARTS', 'INFO'];
        }
        elseif (in_array($levelCode, ['2NDE', '1ERE', 'TERM'])) {
            $subjectCodes = ['MATH', 'FR', 'ANG', 'HIST-GEO', 'PC', 'SVT', 'PHILO', 'ECO', 'INFO', 'EPS'];
        }

        return array_filter($subjects, function($subject) use ($subjectCodes) {
            return in_array($subject->code, $subjectCodes);
        });
    }

    /**
     * Seed sample schedules
     */
    protected function seedSchedules(array $classRooms, array $subjects, AcademicYear $academicYear): void
    {
        $teachers = User::where('role', 'teacher')->limit(5)->get();
        
        if ($teachers->isEmpty()) {
            return;
        }

        $days = ['monday', 'tuesday', 'wednesday', 'thursday', 'friday'];
        $timeSlots = [
            ['08:00', '09:00'],
            ['09:00', '10:00'],
            ['10:15', '11:15'],
            ['11:15', '12:15'],
        ];

        $sampleClasses = array_slice($classRooms, 0, 3);

        foreach ($sampleClasses as $classRoom) {
            $classSubjects = $classRoom->subjects()->take(5)->get();
            
            $scheduleIndex = 0;
            foreach ($days as $dayIndex => $day) {
                for ($slot = 0; $slot < 4; $slot++) {
                    if ($scheduleIndex >= count($classSubjects)) {
                        break 2;
                    }

                    $subject = $classSubjects[$scheduleIndex];
                    $teacher = $teachers->random();

                    try {
                        Schedule::create([
                            'class_room_id' => $classRoom->id,
                            'subject_id' => $subject->id,
                            'teacher_id' => $teacher->id,
                            'academic_year_id' => $academicYear->id,
                            'day_of_week' => $day,
                            'start_time' => $timeSlots[$slot][0],
                            'end_time' => $timeSlots[$slot][1],
                            'room' => $classRoom->room_number,
                        ]);
                    } catch (\Exception $e) {
                        continue;
                    }

                    $scheduleIndex++;
                }
            }
        }
    }
}
