<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\MP\StudentMP;
use App\Models\MP\ClassMP;
use App\Models\SchoolYear;

class DemoMPSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->command->info('🌱 Seeding MP Demo Data...');

        // 1. Get or Create Current School Year
        $year = SchoolYear::firstOrCreate(
            ['name' => '2023-2024'],
            [
                'start_date' => '2023-09-01',
                'end_date' => '2024-06-30',
                'is_current' => true
            ]
        );

        // 2. Ensure we have Classes for MP
        $classes = [
            ['niveau' => 'CP', 'nom' => 'CP A', 'capacity' => 40],
            ['niveau' => 'CE1', 'nom' => 'CE1 A', 'capacity' => 45],
            ['niveau' => 'CM2', 'nom' => 'CM2 A', 'capacity' => 50],
        ];

        foreach ($classes as $c) {
            $class = ClassMP::firstOrCreate(
                [
                    'school_year_id' => $year->id,
                    'niveau' => $c['niveau'],
                    'nom' => $c['nom']
                ],
                [
                    'seuil_minimum' => 15,
                    'seuil_maximum' => $c['capacity'],
                    'effectif_actuel' => 0,
                    'is_active' => true
                ]
            );

            // 3. Populate each class with 5 students if empty
            if ($class->students()->count() === 0) {
                $this->command->info("Creating students for {$class->niveau} {$class->nom}...");

                for ($i = 1; $i <= 5; $i++) {
                    $student = StudentMP::create([
                        'matricule' => 'MP-' . $class->niveau . '-' . rand(1000, 9999),
                        'nom' => $this->getRandomLastName(),
                        'prenoms' => $this->getRandomFirstName(),
                        'date_naissance' => now()->subYears(6), // Approx age
                        'lieu_naissance' => 'Ouagadougou',
                        'sexe' => rand(0, 1) ? 'M' : 'F',
                        'nationalite' => 'Burkinabè',
                        'statut_inscription' => 'nouveau',
                        'is_active' => true
                    ]);

                    // Enroll student
                    \App\Models\MP\EnrollmentMP::create([
                        'student_id' => $student->id,
                        'class_id' => $class->id,
                        'school_year_id' => $year->id,
                        'date_inscription' => now(),
                        'statut' => 'validee',
                        'frais_scolarite' => 50000,
                        'total_a_payer' => 60000,
                        'montant_final' => 60000,
                    ]);

                    // Update class count
                    $class->increment('effectif_actuel');
                }
            }
        }

        $this->command->info('✅ MP Demo Data Seeded!');
    }

    private function getRandomLastName()
    {
        $names = ['OUEDRAOGO', 'SAWADOGO', 'SANKARA', 'COMPAORE', 'KABORE', 'ZONGO', 'DIALLO', 'TRAORE'];
        return $names[array_rand($names)];
    }

    private function getRandomFirstName()
    {
        $names = ['Awa', 'Moussa', 'Fatou', 'Ali', 'Mariam', 'Jean', 'Paul', 'Sophie', 'Ismael', 'David'];
        return $names[array_rand($names)];
    }
}
