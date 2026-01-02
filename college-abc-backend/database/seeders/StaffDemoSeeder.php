<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class StaffDemoSeeder extends Seeder
{
    public function run(): void
    {
        $staff = [
            // Direction
            [
                'first_name' => 'Alidou',
                'last_name' => 'Ouedraogo',
                'email' => 'a.ouedraogo@manegda.bf',
                'role' => 'director',
                'phone' => '+226 70 00 00 01',
                'password' => Hash::make('password'),
                'is_active' => true,
            ],
            // Secrétariat
            [
                'first_name' => 'Fatoumata',
                'last_name' => 'Sawadogo',
                'email' => 'f.sawadogo@manegda.bf',
                'role' => 'secretary',
                'phone' => '+226 71 11 22 33',
                'password' => Hash::make('password'),
                'is_active' => true,
            ],
            // Comptabilité
            [
                'first_name' => 'Issouf',
                'last_name' => 'Zongo',
                'email' => 'i.zongo@manegda.bf',
                'role' => 'accountant',
                'phone' => '+226 76 55 44 33',
                'password' => Hash::make('password'),
                'is_active' => true,
            ],
            // Enseignants
            [
                'first_name' => 'Jean-Baptiste',
                'last_name' => 'Solen',
                'email' => 'jb.solen@manegda.bf',
                'role' => 'teacher',
                'phone' => '+226 78 99 00 11',
                'password' => Hash::make('password'),
                'is_active' => true,
            ],
            [
                'first_name' => 'Mariam',
                'last_name' => 'Traoré',
                'email' => 'm.traore@manegda.bf',
                'role' => 'teacher',
                'phone' => '+226 75 12 34 56',
                'password' => Hash::make('password'),
                'is_active' => true,
            ],
            [
                'first_name' => 'Pierre',
                'last_name' => 'Kabore',
                'email' => 'p.kabore@manegda.bf',
                'role' => 'teacher',
                'phone' => '+226 60 44 55 66',
                'password' => Hash::make('password'),
                'is_active' => true,
            ],
            [
                'first_name' => 'Sarah',
                'last_name' => 'Johnson',
                'email' => 's.johnson@manegda.bf',
                'role' => 'teacher',
                'phone' => '+226 72 33 44 55',
                'password' => Hash::make('password'),
                'is_active' => true,
            ],
            // Parent & Élève de démonstration
            [
                'first_name' => 'Adama',
                'last_name' => 'Cissé',
                'email' => 'parent@manegda.bf',
                'role' => 'parent',
                'phone' => '+226 74 00 11 22',
                'password' => Hash::make('password'),
                'is_active' => true,
            ],
            [
                'first_name' => 'Koffi',
                'last_name' => 'Cissé',
                'email' => 'student@manegda.bf',
                'role' => 'student',
                'phone' => '+226 74 33 44 55',
                'password' => Hash::make('password'),
                'is_active' => true,
            ]
        ];

        foreach ($staff as $s) {
            $user = User::updateOrCreate(
                ['email' => $s['email']],
                $s
            );

            // Lien élève si c'est un student
            if ($s['role'] === 'student') {
                \Illuminate\Support\Facades\DB::table('students')->updateOrInsert(
                    ['matricule' => 'STD-DEMO-001'],
                    [
                        'first_name' => $s['first_name'],
                        'last_name' => $s['last_name'],
                        'user_id' => $user->id,
                        'gender' => 'M',
                        'date_of_birth' => '2012-05-10',
                        'status' => 'active'
                    ]
                );
            }
        }
    }
}
