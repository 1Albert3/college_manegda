<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Parent User
        \App\Models\User::create([
            'name' => 'M. & Mme OUEDRAOGO',
            'email' => 'parent@test.com',
            'password' => \Illuminate\Support\Facades\Hash::make('password'),
            'role' => 'parent',
        ]);

        // Admin User
        \App\Models\User::create([
            'name' => 'Administrateur',
            'email' => 'admin@college-abc.bf',
            'password' => \Illuminate\Support\Facades\Hash::make('password'),
            'role' => 'admin',
        ]);
    }
}
