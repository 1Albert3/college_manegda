<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\MP\SubjectMP;

class DemoMPSubjectsSeeder extends Seeder
{
    public function run(): void
    {
        $this->command->info('🌱 Seeding MP Demo Subjects...');

        $subjects = [
            // MATIERES PRIMAIRE (CP -> CM2)
            ['code' => 'MAT', 'nom' => 'Mathématiques', 'cat' => 'sciences', 'coeffs' => [2, 4, 4, 4]], // [CP/CE1, CE2, CM1/CM2]
            ['code' => 'FRA', 'nom' => 'Français', 'cat' => 'communication', 'coeffs' => [2, 4, 4, 4]],
            ['code' => 'ECR', 'nom' => 'Écriture', 'cat' => 'communication', 'coeffs' => [1, 2, 1, 1]],
            ['code' => 'LEC', 'nom' => 'Lecture', 'cat' => 'communication', 'coeffs' => [2, 3, 2, 2]],
            ['code' => 'HIS', 'nom' => 'Histoire', 'cat' => 'eveil', 'coeffs' => [0, 1, 2, 2]],
            ['code' => 'GEO', 'nom' => 'Géographie', 'cat' => 'eveil', 'coeffs' => [0, 1, 2, 2]],
            ['code' => 'EPS', 'nom' => 'Éducation physique', 'cat' => 'eps', 'coeffs' => [1, 1, 1, 1]],

            // MATIERES MATERNELLE (PS -> GS) - coeffs Maternelle
            ['code' => 'LAN', 'nom' => 'Langage', 'cat' => 'communication', 'coeff_mat' => 1],
            ['code' => 'MOT', 'nom' => 'Motricité', 'cat' => 'eps', 'coeff_mat' => 1],
            ['code' => 'ART', 'nom' => 'Activités artistiques', 'cat' => 'arts', 'coeff_mat' => 1],
        ];

        foreach ($subjects as $s) {
            SubjectMP::firstOrCreate(
                ['code' => $s['code']],
                [
                    'nom' => $s['nom'],
                    'categorie' => $s['cat'],
                    'description' => $s['nom'],
                    'is_active' => true,
                    // Mapping des coeffs
                    'coefficient_maternelle' => $s['coeff_mat'] ?? 0,
                    'coefficient_cp_ce1' => $s['coeffs'][0] ?? 0,
                    'coefficient_ce2' => $s['coeffs'][1] ?? 0,
                    'coefficient_cm1_cm2' => $s['coeffs'][3] ?? ($s['coeffs'][2] ?? 0), // Simplification
                    'type_evaluation' => ($s['coeff_mat'] ?? 0) > 0 ? 'competences' : 'notes'
                ]
            );
        }

        $this->command->info('✅ MP Demo Subjects Seeded!');
    }
}
