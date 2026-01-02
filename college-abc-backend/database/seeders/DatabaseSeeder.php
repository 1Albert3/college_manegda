<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Core\Entities\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->command->info('🌱 Starting database seeding...');

        // 1. Seeder des rôles et permissions Core
        $this->call([
            \Modules\Core\Database\Seeders\RolesAndPermissionsSeeder::class,
        ]);

        // 2. Créer utilisateur super admin
        $this->createSuperAdmin();

        // 3. Seeder Academic (matières, classes, années scolaires)
        // $this->call([
        //     \Modules\Academic\Database\Seeders\AcademicSeeder::class,
        // ]);

        // 4. Seeder Structure Ecole (Cycles, Niveaux, Classes)
        $this->call([
            \Database\Seeders\SchoolStructureSeeder::class,
        ]);

        // 5. Seeder Utilisateurs de test
        $this->call([
            \Database\Seeders\UserSeeder::class,
        ]);

        $this->command->info('✅ Database seeded successfully!');
        $this->command->info('🎯 Utilisateur admin: admin@college-abc.com / password123');
    }

    private function createSuperAdmin()
    {
        $this->command->info('👤 Creating super admin user...');

        // Vérifier si l'admin existe déjà
        $existingAdmin = DB::table('users')->where('email', 'admin@college-abc.com')->first();

        if ($existingAdmin) {
            $this->command->info('✅ Super admin already exists: admin@college-abc.com / password123');
            return;
        }

        // Créer l'admin avec un insert direct (évite les traits UUID)
        $adminId = DB::table('users')->insertGetId([
            'id' => Str::uuid(),
            'first_name' => 'Super',
            'last_name' => 'Administrateur',
            'email' => 'admin@college-abc.com',
            'password' => Hash::make('password123'),
            'role' => 'super_admin',
            'phone' => '+22670000000',
            'is_active' => true,
            'email_verified_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Maintenant que l'utilisateur existe, utiliser le modèle pour assigner le rôle
        $admin = User::find($adminId);
        $admin->assignRole('super_admin');

        $this->command->info('✅ Super admin created: admin@college-abc.com / password123');
    }
}
