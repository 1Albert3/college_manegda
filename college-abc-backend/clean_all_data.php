<?php

/**
 * Clean All Data Script
 * Removes all data from all databases while preserving:
 * - Database structure (tables, migrations)
 * - One admin user for login
 * - School year configuration
 */

require_once __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Schema;

echo "🗑️  Nettoyage complet de toutes les données...\n\n";

// Helper function to truncate table if exists
function truncateIfExists($connection, $table)
{
    try {
        if (Schema::connection($connection)->hasTable($table)) {
            DB::connection($connection)->table($table)->truncate();
            echo "  ✓ Truncated: $table\n";
        }
    } catch (Exception $e) {
        // Skip silently
    }
}

// 1. Clean MP database
echo "📦 Nettoyage de school_mp...\n";
try {
    DB::connection('school_mp')->statement('SET FOREIGN_KEY_CHECKS=0');
    $tables = ['grades_mp', 'evaluations_mp', 'attendances_mp', 'guardians_mp', 'enrollments_mp', 'students_mp', 'teachers_mp', 'subjects_mp', 'classes_mp', 'schedules'];
    foreach ($tables as $table) {
        truncateIfExists('school_mp', $table);
    }
    DB::connection('school_mp')->statement('SET FOREIGN_KEY_CHECKS=1');
    echo "  ✅ school_mp nettoyé\n\n";
} catch (Exception $e) {
    echo "  ⚠️  Erreur: " . $e->getMessage() . "\n\n";
}

// 2. Clean College database
echo "📦 Nettoyage de school_college...\n";
try {
    DB::connection('school_college')->statement('SET FOREIGN_KEY_CHECKS=0');
    $tables = ['grades_college', 'evaluations_college', 'attendances_college', 'guardians_college', 'enrollments_college', 'students_college', 'teachers_college', 'subjects_college', 'classes_college', 'schedules'];
    foreach ($tables as $table) {
        truncateIfExists('school_college', $table);
    }
    DB::connection('school_college')->statement('SET FOREIGN_KEY_CHECKS=1');
    echo "  ✅ school_college nettoyé\n\n";
} catch (Exception $e) {
    echo "  ⚠️  Erreur: " . $e->getMessage() . "\n\n";
}

// 3. Clean Lycee database
echo "📦 Nettoyage de school_lycee...\n";
try {
    DB::connection('school_lycee')->statement('SET FOREIGN_KEY_CHECKS=0');
    $tables = ['grades_lycee', 'evaluations_lycee', 'attendances_lycee', 'guardians_lycee', 'enrollments_lycee', 'students_lycee', 'teachers_lycee', 'subjects_lycee', 'classes_lycee', 'schedules'];
    foreach ($tables as $table) {
        truncateIfExists('school_lycee', $table);
    }
    DB::connection('school_lycee')->statement('SET FOREIGN_KEY_CHECKS=1');
    echo "  ✅ school_lycee nettoyé\n\n";
} catch (Exception $e) {
    echo "  ⚠️  Erreur: " . $e->getMessage() . "\n\n";
}

// 4. Clean Core database (mysql/school_core)
echo "📦 Nettoyage de school_core (mysql)...\n";
try {
    DB::statement('SET FOREIGN_KEY_CHECKS=0');

    // Remove all users except we'll recreate admin
    truncateIfExists('mysql', 'users');
    truncateIfExists('mysql', 'personal_access_tokens');

    // Clear Spatie permission tables if they exist
    truncateIfExists('mysql', 'model_has_roles');
    truncateIfExists('mysql', 'model_has_permissions');
    truncateIfExists('mysql', 'role_has_permissions');
    truncateIfExists('mysql', 'roles');
    truncateIfExists('mysql', 'permissions');

    // Clear school years (will recreate one)
    truncateIfExists('mysql', 'school_years');

    DB::statement('SET FOREIGN_KEY_CHECKS=1');
    echo "  ✅ school_core nettoyé\n\n";
} catch (Exception $e) {
    echo "  ⚠️  Erreur: " . $e->getMessage() . "\n\n";
}

// 5. Recreate essential data
echo "🔧 Création des données essentielles...\n";

// Create school year
try {
    $schoolYearId = Str::uuid()->toString();
    DB::table('school_years')->insert([
        'id' => $schoolYearId,
        'name' => '2024-2025',
        'start_date' => '2024-09-01',
        'end_date' => '2025-07-15',
        'is_current' => true,
        'created_at' => now(),
        'updated_at' => now(),
    ]);
    echo "  ✅ Année scolaire 2024-2025 créée\n";
} catch (Exception $e) {
    echo "  ⚠️ Année scolaire: " . $e->getMessage() . "\n";
}

// Create roles if table exists
if (Schema::hasTable('roles')) {
    try {
        $roles = [
            ['name' => 'direction', 'display_name' => 'Direction', 'description' => 'Personnel de direction'],
            ['name' => 'secretariat', 'display_name' => 'Secrétariat', 'description' => 'Personnel du secrétariat'],
            ['name' => 'comptabilite', 'display_name' => 'Comptabilité', 'description' => 'Personnel comptable'],
            ['name' => 'enseignant', 'display_name' => 'Enseignant', 'description' => 'Professeurs et instituteurs'],
            ['name' => 'parent', 'display_name' => 'Parent', 'description' => 'Parents d\'élèves'],
            ['name' => 'eleve', 'display_name' => 'Élève', 'description' => 'Élèves inscrits'],
        ];

        foreach ($roles as $role) {
            DB::table('roles')->insert([
                'id' => Str::uuid()->toString(),
                'name' => $role['name'],
                'display_name' => $role['display_name'],
                'description' => $role['description'],
                'is_system' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
        echo "  ✅ Rôles créés\n";
    } catch (Exception $e) {
        echo "  ⚠️ Rôles: " . $e->getMessage() . "\n";
    }
}

// Create admin user
try {
    $adminId = Str::uuid()->toString();
    DB::table('users')->insert([
        'id' => $adminId,
        'first_name' => 'Administrateur',
        'last_name' => 'Système',
        'email' => 'admin@wend-manegda.bf',
        'password' => Hash::make('Admin@2024'),
        'role' => 'direction',
        'is_active' => true,
        'email_verified_at' => now(),
        'created_at' => now(),
        'updated_at' => now(),
    ]);
    echo "  ✅ Utilisateur admin créé\n";
} catch (Exception $e) {
    echo "  ⚠️ Admin user: " . $e->getMessage() . "\n";
}

echo "\n";
echo "═══════════════════════════════════════════════════════════════\n";
echo "              🎉 NETTOYAGE TERMINÉ AVEC SUCCÈS!                 \n";
echo "═══════════════════════════════════════════════════════════════\n";
echo "\n";
echo "📋 IDENTIFIANT DE CONNEXION:\n";
echo "   Email:       admin@wend-manegda.bf\n";
echo "   Mot de passe: Admin@2024\n";
echo "\n";
echo "📅 Année scolaire: 2024-2025\n";
echo "\n";
echo "Vous pouvez maintenant créer vos données réelles depuis le site!\n";
echo "\n";
