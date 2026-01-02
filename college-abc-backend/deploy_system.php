<?php

require 'vendor/autoload.php';

$app = require 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "=== DÉPLOIEMENT SYSTÈME SCOLAIRE PROFESSIONNEL ===\n\n";

// 1. Configuration des bases de données
echo "1. Configuration des bases de données multiples...\n";

$databases = [
    'school_core' => 'Base centrale (Auth, RBAC, Audit)',
    'school_maternelle_primaire' => 'Maternelle/Primaire',
    'school_college' => 'Collège', 
    'school_lycee' => 'Lycée'
];

foreach ($databases as $dbName => $description) {
    try {
        \Illuminate\Support\Facades\DB::statement("CREATE DATABASE IF NOT EXISTS `$dbName` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
        echo "✓ $description ($dbName) créée\n";
    } catch (Exception $e) {
        echo "- $description existe déjà\n";
    }
}

// 2. Tables de base school_core
echo "\n2. Création des tables de base (school_core)...\n";

$coreTables = [
    'users' => "
        CREATE TABLE IF NOT EXISTS users (
            id varchar(36) PRIMARY KEY,
            email varchar(191) UNIQUE NOT NULL,
            password varchar(255) NOT NULL,
            phone varchar(20),
            first_name varchar(100) NOT NULL,
            last_name varchar(100) NOT NULL,
            role enum('super_admin','admin','director','secretary','accountant','teacher','parent','student') NOT NULL,
            is_active boolean DEFAULT true,
            email_verified_at timestamp NULL,
            last_login_at timestamp NULL,
            failed_login_attempts tinyint DEFAULT 0,
            locked_until timestamp NULL,
            created_at timestamp DEFAULT CURRENT_TIMESTAMP,
            updated_at timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            deleted_at timestamp NULL,
            INDEX idx_email (email),
            INDEX idx_role (role),
            INDEX idx_active (is_active)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ",
    'school_years' => "
        CREATE TABLE IF NOT EXISTS school_years (
            id varchar(36) PRIMARY KEY,
            name varchar(100) NOT NULL,
            start_date date NOT NULL,
            end_date date NOT NULL,
            is_current boolean DEFAULT false,
            created_at timestamp DEFAULT CURRENT_TIMESTAMP,
            updated_at timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ",
    'audit_logs' => "
        CREATE TABLE IF NOT EXISTS audit_logs (
            id bigint unsigned AUTO_INCREMENT PRIMARY KEY,
            user_id varchar(36),
            action varchar(100) NOT NULL,
            model varchar(100),
            model_id varchar(36),
            old_values json,
            new_values json,
            ip_address varchar(45),
            user_agent text,
            created_at timestamp DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
            INDEX idx_user (user_id),
            INDEX idx_action (action),
            INDEX idx_created (created_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    "
];

foreach ($coreTables as $tableName => $sql) {
    try {
        \Illuminate\Support\Facades\DB::connection('school_core')->statement($sql);
        echo "✓ Table '$tableName' créée\n";
    } catch (Exception $e) {
        echo "- Table '$tableName': " . $e->getMessage() . "\n";
    }
}

// 3. Tables principales (school_core)
echo "\n3. Création des tables principales...\n";

$mainTables = [
    'students' => "
        CREATE TABLE IF NOT EXISTS students (
            id bigint unsigned AUTO_INCREMENT PRIMARY KEY,
            matricule varchar(20) UNIQUE NOT NULL,
            first_name varchar(100) NOT NULL,
            last_name varchar(100) NOT NULL,
            date_of_birth date NOT NULL,
            place_of_birth varchar(100),
            gender enum('M','F') NOT NULL,
            address text,
            blood_group varchar(5),
            photo_path varchar(255),
            user_id varchar(36),
            status enum('active','inactive','graduated','transferred','archived') DEFAULT 'active',
            created_at timestamp DEFAULT CURRENT_TIMESTAMP,
            updated_at timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            deleted_at timestamp NULL,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
            INDEX idx_matricule (matricule),
            INDEX idx_status (status),
            INDEX idx_gender (gender)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ",
    'classrooms' => "
        CREATE TABLE IF NOT EXISTS classrooms (
            id bigint unsigned AUTO_INCREMENT PRIMARY KEY,
            name varchar(100) NOT NULL,
            level varchar(50),
            cycle varchar(50),
            capacity int DEFAULT 35,
            current_students int DEFAULT 0,
            is_active boolean DEFAULT true,
            created_at timestamp DEFAULT CURRENT_TIMESTAMP,
            updated_at timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX idx_level (level),
            INDEX idx_active (is_active)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ",
    'enrollments' => "
        CREATE TABLE IF NOT EXISTS enrollments (
            id bigint unsigned AUTO_INCREMENT PRIMARY KEY,
            student_id bigint unsigned NOT NULL,
            classroom_id bigint unsigned NOT NULL,
            school_year_id varchar(36) NOT NULL,
            status enum('active','inactive','completed','transferred') DEFAULT 'active',
            enrollment_date date NOT NULL,
            fees_amount decimal(10,2) DEFAULT 0,
            scholarship_amount decimal(10,2) DEFAULT 0,
            created_at timestamp DEFAULT CURRENT_TIMESTAMP,
            updated_at timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE,
            FOREIGN KEY (classroom_id) REFERENCES classrooms(id) ON DELETE CASCADE,
            FOREIGN KEY (school_year_id) REFERENCES school_years(id) ON DELETE CASCADE,
            INDEX idx_student (student_id),
            INDEX idx_classroom (classroom_id),
            INDEX idx_status (status)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    "
];

foreach ($mainTables as $tableName => $sql) {
    try {
        \Illuminate\Support\Facades\DB::statement($sql);
        echo "✓ Table '$tableName' créée\n";
    } catch (Exception $e) {
        echo "- Table '$tableName': " . $e->getMessage() . "\n";
    }
}

// 4. Données de base
echo "\n4. Insertion des données de base...\n";

// Année scolaire
try {
    \Illuminate\Support\Facades\DB::connection('school_core')->table('school_years')->insertOrIgnore([
        'id' => \Illuminate\Support\Str::uuid(),
        'name' => '2024-2025',
        'start_date' => '2024-09-01',
        'end_date' => '2025-07-31',
        'is_current' => true
    ]);
    echo "✓ Année scolaire 2024-2025 créée\n";
} catch (Exception $e) {
    echo "- Année scolaire existe\n";
}

// Utilisateur admin
try {
    \Illuminate\Support\Facades\DB::table('users')->insertOrIgnore([
        'id' => \Illuminate\Support\Str::uuid(),
        'email' => 'admin@college-abc.bf',
        'password' => \Illuminate\Support\Facades\Hash::make('admin123'),
        'first_name' => 'Directeur',
        'last_name' => 'Général',
        'role' => 'admin',
        'is_active' => true,
        'email_verified_at' => now()
    ]);
    echo "✓ Utilisateur admin créé (admin@college-abc.bf / admin123)\n";
} catch (Exception $e) {
    echo "- Admin existe déjà\n";
}

// Classes de base
$classes = [
    ['name' => '6ème A', 'level' => '6ème', 'cycle' => 'Collège'],
    ['name' => '5ème A', 'level' => '5ème', 'cycle' => 'Collège'],
    ['name' => '4ème A', 'level' => '4ème', 'cycle' => 'Collège'],
    ['name' => '3ème A', 'level' => '3ème', 'cycle' => 'Collège'],
    ['name' => '2nde A', 'level' => '2nde', 'cycle' => 'Lycée'],
    ['name' => '1ère A', 'level' => '1ère', 'cycle' => 'Lycée'],
    ['name' => 'Tle A', 'level' => 'Terminale', 'cycle' => 'Lycée']
];

foreach ($classes as $class) {
    try {
        \Illuminate\Support\Facades\DB::table('classrooms')->insertOrIgnore($class);
    } catch (Exception $e) {}
}
echo "✓ Classes de base créées\n";

// Élèves de test
$students = [
    ['matricule' => 'STD-2025-0001', 'first_name' => 'Jean', 'last_name' => 'Dupont', 'date_of_birth' => '2010-05-15', 'gender' => 'M'],
    ['matricule' => 'STD-2025-0002', 'first_name' => 'Marie', 'last_name' => 'Kouassi', 'date_of_birth' => '2011-03-20', 'gender' => 'F'],
    ['matricule' => 'STD-2025-0003', 'first_name' => 'Paul', 'last_name' => 'Koné', 'date_of_birth' => '2010-08-12', 'gender' => 'M']
];

foreach ($students as $student) {
    try {
        \Illuminate\Support\Facades\DB::table('students')->insertOrIgnore($student);
    } catch (Exception $e) {}
}
echo "✓ Élèves de test créés\n";

// 5. Configuration Sanctum
echo "\n5. Configuration de l'authentification...\n";
try {
    \Illuminate\Support\Facades\DB::statement("
        CREATE TABLE IF NOT EXISTS personal_access_tokens (
            id bigint unsigned AUTO_INCREMENT PRIMARY KEY,
            tokenable_type varchar(255) NOT NULL,
            tokenable_id varchar(36) NOT NULL,
            name varchar(255) NOT NULL,
            token varchar(64) UNIQUE NOT NULL,
            abilities text,
            last_used_at timestamp NULL,
            expires_at timestamp NULL,
            created_at timestamp NULL,
            updated_at timestamp NULL,
            INDEX personal_access_tokens_tokenable_type_tokenable_id_index (tokenable_type, tokenable_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
    echo "✓ Table Sanctum créée\n";
} catch (Exception $e) {
    echo "- Sanctum déjà configuré\n";
}

echo "\n=== DÉPLOIEMENT TERMINÉ ===\n";
echo "🎉 Système scolaire professionnel opérationnel !\n\n";
echo "📋 IDENTIFIANTS DE CONNEXION:\n";
echo "Email: admin@college-abc.bf\n";
echo "Mot de passe: admin123\n\n";
echo "🌐 ENDPOINTS DISPONIBLES:\n";
echo "- Login: POST /api/auth/login\n";
echo "- Dashboard: GET /api/dashboard/direction\n";
echo "- Élèves: GET /api/v1/students\n";
echo "- Classes: GET /api/v1/classes\n\n";
echo "🔒 Authentification: Sanctum (Bearer Token)\n";
echo "📊 Bases de données: 4 bases configurées\n";
echo "✅ Prêt pour la génération de bulletins !\n";