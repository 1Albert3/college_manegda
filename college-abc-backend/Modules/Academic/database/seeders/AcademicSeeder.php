<?php

namespace Modules\Academic\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Academic\Entities\AcademicYear;
use Modules\Academic\Entities\Subject;
use Modules\Academic\Entities\ClassRoom;
use Carbon\Carbon;

class AcademicSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->command->info('🌱 Seeding Academic Module données scolaires...');

        // 1. Créer des années académiques
        $this->createAcademicYears();

        // 2. Créer les matières
        $this->createSubjects();

        // 3. Créer les classes
        $this->createClassRooms();

        $this->command->info('✅ Academic Module seeded successfully!');
    }

    private function createAcademicYears()
    {
        $this->command->info('📅 Creating academic years...');

        $academicYears = [
            [
                'name' => '2024-2025',
                'start_date' => Carbon::parse('2024-09-02'),
                'end_date' => Carbon::parse('2025-06-28'),
                'status' => 'active',
                'is_current' => true,
                'description' => 'Année scolaire 2024-2025',
            ],
            [
                'name' => '2023-2024',
                'start_date' => Carbon::parse('2023-09-04'),
                'end_date' => Carbon::parse('2024-06-29'),
                'status' => 'completed',
                'is_current' => false,
                'description' => 'Année scolaire 2023-2024',
            ],
            [
                'name' => '2025-2026',
                'start_date' => Carbon::parse('2025-09-01'),
                'end_date' => Carbon::parse('2026-06-27'),
                'status' => 'planned',
                'is_current' => false,
                'description' => 'Année scolaire 2025-2026 (prévisionnelle)',
            ],
        ];

        foreach ($academicYears as $year) {
            AcademicYear::firstOrCreate(
                ['name' => $year['name']],
                $year
            );
        }

        $this->command->info('✅ Academic years created');
    }

    private function createSubjects()
    {
        $this->command->info('📚 Creating school subjects...');

        $subjects = [
            // Sciences
            ['name' => 'Mathématiques', 'code' => 'MATH', 'category' => 'sciences', 'coefficients' => 4, 'weekly_hours' => 5, 'level_type' => 'both', 'description' => 'Arithmétique, Algèbre, Géométrie'],
            ['name' => 'Physique Chimie', 'code' => 'PC', 'category' => 'sciences', 'coefficients' => 3, 'weekly_hours' => 4, 'level_type' => 'secondary', 'description' => 'Physique et Chimie'],
            ['name' => 'Sciences de la Vie et de la Terre', 'code' => 'SVT', 'category' => 'sciences', 'coefficients' => 2, 'weekly_hours' => 3, 'level_type' => 'secondary', 'description' => 'Biologie, Géologie, Écologie'],
            ['name' => 'Technologie', 'code' => 'TECH', 'category' => 'technology', 'coefficients' => 2, 'weekly_hours' => 3, 'level_type' => 'secondary', 'description' => 'Informatique, Électronique, Mécanique'],

            // Littérature et Langues
            ['name' => 'Français', 'code' => 'FRAN', 'category' => 'literature', 'coefficients' => 4, 'weekly_hours' => 5, 'level_type' => 'both', 'description' => 'Grammaire, Littérature, Expression'],
            ['name' => 'Anglais', 'code' => 'ANGL', 'category' => 'language', 'coefficients' => 2, 'weekly_hours' => 3, 'level_type' => 'secondary', 'description' => 'LV1 Anglais'],
            ['name' => 'Histoire Géographie', 'code' => 'HIST', 'category' => 'social_studies', 'coefficients' => 2, 'weekly_hours' => 3, 'level_type' => 'secondary', 'description' => 'Histoire et Géographie'],
            ['name' => 'Éducation Civique', 'code' => 'EC', 'category' => 'social_studies', 'coefficients' => 1, 'weekly_hours' => 1, 'level_type' => 'secondary', 'description' => 'Éducation à la Citoyenneté'],

            // Arts et Éducation Physique
            ['name' => 'Arts Plastiques', 'code' => 'ARTS', 'category' => 'arts', 'coefficients' => 1, 'weekly_hours' => 2, 'level_type' => 'both', 'description' => 'Dessin, Peinture, Sculpture'],
            ['name' => 'Éducation Physique et Sportive', 'code' => 'EPS', 'category' => 'physical_education', 'coefficients' => 1, 'weekly_hours' => 2, 'level_type' => 'both', 'description' => 'Activités Sportives'],
            ['name' => 'Musique', 'code' => 'MUSI', 'category' => 'arts', 'coefficients' => 1, 'weekly_hours' => 2, 'level_type' => 'both', 'description' => 'Éducation Musicale'],

            // Langues Africaines (Burkina Faso spécifique)
            ['name' => 'Moore', 'code' => 'MOOR', 'category' => 'language', 'coefficients' => 1, 'weekly_hours' => 2, 'level_type' => 'both', 'description' => 'Langue nationale'],
            ['name' => 'Dioula', 'code' => 'DIOL', 'category' => 'language', 'coefficients' => 1, 'weekly_hours' => 2, 'level_type' => 'secondary', 'description' => 'Langue véhiculaire'],

            // Enseignement Religieux
            ['name' => 'Éducation Islamique', 'code' => 'ISLA', 'category' => 'social_studies', 'coefficients' => 1, 'weekly_hours' => 2, 'level_type' => 'both', 'description' => 'Enseignement Religieux'],
            ['name' => 'Éducation Chrétienne', 'code' => 'CHRE', 'category' => 'social_studies', 'coefficients' => 1, 'weekly_hours' => 2, 'level_type' => 'both', 'description' => 'Instruction Religieuse'],
        ];

        foreach ($subjects as $subject) {
            Subject::firstOrCreate(
                ['code' => $subject['code']],
                $subject
            );
        }

        $this->command->info('✅ Subjects created');
    }

    private function createClassRooms()
    {
        $this->command->info('🏫 Creating class rooms...');

        $classLevels = [
            ['level' => '6ème', 'capacity' => 30, 'streams' => ['']],
            ['level' => '5ème', 'capacity' => 28, 'streams' => ['']],
            ['level' => '4ème', 'capacity' => 26, 'streams' => ['']],
            ['level' => '3ème', 'capacity' => 24, 'streams' => ['']],
            ['level' => 'Seconde', 'capacity' => 25, 'streams' => ['A', 'B', 'C']],
            ['level' => 'Première', 'capacity' => 22, 'streams' => ['Sciences', 'Littéraire', 'Économique']],
            ['level' => 'Terminale', 'capacity' => 20, 'streams' => ['Sciences', 'Littéraire', 'Économique']],
        ];

        $classes = [];
        $counter = 1;

        foreach ($classLevels as $levelInfo) {
            foreach ($levelInfo['streams'] as $stream) {
                $className = $levelInfo['level'] . ($stream ? ' ' . $stream : '');

                if ($levelInfo['level'] === 'Seconde' && !in_array($stream, ['A', 'B', 'C'])) {
                    continue; // Seconde n'a que A, B, C
                }

                if (in_array($levelInfo['level'], ['Première', 'Terminale']) && !in_array($stream, ['Sciences', 'Littéraire', 'Économique'])) {
                    continue; // Primaire et terminale ont des streams spécifiques
                }

                $classes[] = [
                    'name' => $className,
                    'level' => $levelInfo['level'],
                    'stream' => $stream,
                    'capacity' => $levelInfo['capacity'],
                    'current_students_count' => 0,
                    'status' => 'active',
                    'description' => "Classe {$counter}: {$className}",
                ];

                $counter++;
            }
        }

        foreach ($classes as $class) {
            ClassRoom::firstOrCreate(
                ['name' => $class['name']],
                $class
            );
        }

        $this->command->info('✅ Class rooms created');
    }
}
