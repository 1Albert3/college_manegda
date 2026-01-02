<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class TestUsersSeeder extends Seeder
{
    public function run(): void
    {
        // Admin/Direction
        User::updateOrCreate(
            ['email' => 'admin@college.bf'],
            [
                'name' => 'Directeur COLLEGE',
                'email' => 'admin@college.bf',
                'password' => Hash::make('password123'),
                'role' => 'admin',
                'is_active' => true,
            ]
        );

        // Secrétaire
        User::updateOrCreate(
            ['email' => 'secretaire@college.bf'],
            [
                'name' => 'Marie OUEDRAOGO',
                'email' => 'secretaire@college.bf',
                'password' => Hash::make('password123'),
                'role' => 'secretary',
                'is_active' => true,
            ]
        );

        // Comptable
        User::updateOrCreate(
            ['email' => 'comptable@college.bf'],
            [
                'name' => 'Paul KABORE',
                'email' => 'comptable@college.bf',
                'password' => Hash::make('password123'),
                'role' => 'accountant',
                'is_active' => true,
            ]
        );

        // Enseignant
        User::updateOrCreate(
            ['email' => 'enseignant@college.bf'],
            [
                'name' => 'Jean SAWADOGO',
                'email' => 'enseignant@college.bf',
                'password' => Hash::make('password123'),
                'role' => 'teacher',
                'is_active' => true,
            ]
        );

        echo "Utilisateurs de test créés:\n";
        echo "- admin@college.bf (password123) - Admin\n";
        echo "- secretaire@college.bf (password123) - Secrétaire\n";
        echo "- comptable@college.bf (password123) - Comptable\n";
        echo "- enseignant@college.bf (password123) - Enseignant\n";
    }
}