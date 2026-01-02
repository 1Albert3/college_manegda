<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class SchoolStructureSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // 1. Cycle Secondaire
        $cycleSecondaire = \App\Models\Cycle::create([
            'name' => 'Secondaire',
            'slug' => 'secondaire'
        ]);

        // 2. Niveaux (Collège + Lycée)
        $levels = [
            ['name' => '6ème', 'code' => '6EME'],
            ['name' => '5ème', 'code' => '5EME'],
            ['name' => '4ème', 'code' => '4EME'],
            ['name' => '3ème', 'code' => '3EME'],
            ['name' => '2nde', 'code' => '2NDE'],
            ['name' => '1ère', 'code' => '1ERE'],
            ['name' => 'Terminale', 'code' => 'TLE'],
        ];

        foreach ($levels as $levelData) {
            $level = $cycleSecondaire->levels()->create($levelData);

            // 3. Création d'une classe par défaut pour chaque niveau (Ex: 6ème A)
            $level->classrooms()->create([
                'name' => $levelData['name'] . ' A',
                'capacity' => 50
            ]);
        }
    }
}
