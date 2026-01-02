<?php

namespace Modules\Academic\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Academic\Entities\Subject;
use Modules\Academic\Entities\ClassRoom;

class SubjectsFixSeeder extends Seeder
{
    public function run(): void
    {
        $this->command->info('🔧 Fixing Subjects...');

        // 1. Create Subjects if they don't exist
        $subjectsData = [
            ['name' => 'Mathématiques', 'code' => 'MATH', 'coefficient' => 3, 'color' => '#3B82F6'],
            ['name' => 'Français', 'code' => 'FR', 'coefficient' => 3, 'color' => '#EF4444'],
            ['name' => 'Anglais', 'code' => 'ANG', 'coefficient' => 2, 'color' => '#10B981'],
            ['name' => 'Histoire-Géographie', 'code' => 'HIST-GEO', 'coefficient' => 2, 'color' => '#F59E0B'],
            ['name' => 'Sciences Physiques', 'code' => 'PC', 'coefficient' => 2, 'color' => '#8B5CF6'],
            ['name' => 'Sciences de la Vie et de la Terre', 'code' => 'SVT', 'coefficient' => 2, 'color' => '#06B6D4'],
            ['name' => 'Éducation Physique et Sportive', 'code' => 'EPS', 'coefficient' => 1, 'color' => '#EC4899'],
            ['name' => 'Arts Plastiques', 'code' => 'ARTS', 'coefficient' => 1, 'color' => '#F97316'],
            ['name' => 'Informatique', 'code' => 'INFO', 'coefficient' => 2, 'color' => '#8B5CF6'],
        ];

        $createdSubjects = [];

        foreach ($subjectsData as $data) {
            $subject = Subject::firstOrCreate(
                ['code' => $data['code']],
                [
                    'name' => $data['name'],
                    'coefficient' => $data['coefficient'],
                    'description' => "Matière {$data['name']}",
                    'color' => $data['color'],
                    'is_active' => true,
                ]
            );
            $createdSubjects[] = $subject;
        }

        $this->command->info('✓ Subjects ensured: ' . count($createdSubjects));

        // 2. Attach to ALL existing classrooms
        // We assume ClassRoom model works and points to 'classrooms' table
        try {
            $classRooms = ClassRoom::all();
            
            if ($classRooms->isEmpty()) {
                // Fallback: maybe App\Models\Classroom was used and table is 'classrooms'
                // trying to use the Module entity.
                $this->command->warn('No classrooms found via Modules\Academic\Entities\ClassRoom.');
            } else {
                foreach ($classRooms as $classRoom) {
                    $this->command->info("Processing Class: {$classRoom->name}");
                    
                    // Simple logic: Attach ALL subjects to ALL classes for now to ensure visibility
                    // In a real app, we filter by level (6eme vs Tle), but here blocked UI is priority.
                    
                    foreach ($createdSubjects as $subject) {
                        // Check if already attached
                        if (!$classRoom->subjects()->where('subject_id', $subject->id)->exists()) {
                            $classRoom->subjects()->attach($subject->id, [
                                'coefficient' => $subject->coefficient,
                                'created_at' => now(),
                                'updated_at' => now(),
                            ]);
                        }
                    }
                }
                $this->command->info('✓ Subjects attached to ' . $classRooms->count() . ' classrooms.');
            }
        } catch (\Exception $e) {
            $this->command->error("Error attaching subjects: " . $e->getMessage());
        }
    }
}
