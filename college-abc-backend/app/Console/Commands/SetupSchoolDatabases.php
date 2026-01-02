<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Commande pour créer et migrer les bases de données du système scolaire
 */
class SetupSchoolDatabases extends Command
{
    protected $signature = 'school:setup 
                            {--fresh : Supprimer et recréer les tables}
                            {--seed : Exécuter les seeders après migration}';

    protected $description = 'Configure les 4 bases de données du système scolaire';

    private array $databases = [
        'school_core' => 'Base centrale (utilisateurs, rôles)',
        'school_maternelle_primaire' => 'Base Maternelle/Primaire',
        'school_college' => 'Base Collège',
        'school_lycee' => 'Base Lycée',
    ];


    public function handle(): int
    {
        $this->info('🏫 Configuration du Système Scolaire Wend-Manegda');
        $this->info('================================================');
        $this->newLine();

        // Étape 1 : Créer les bases de données si elles n'existent pas
        $this->createDatabases();

        // Étape 2 : Exécuter les migrations de base
        $this->runMigrations();

        // Étape 3 : Seeder si demandé
        if ($this->option('seed')) {
            $this->runSeeder();
        }

        $this->newLine();
        $this->info('✅ Configuration terminée avec succès!');

        return Command::SUCCESS;
    }

    /**
     * Créer les bases de données
     */
    private function createDatabases(): void
    {
        $this->info('📦 Vérification/Création des bases de données...');

        // Connexion directe sans base de données spécifiée
        $host = config('database.connections.mysql.host');
        $port = config('database.connections.mysql.port');
        $username = config('database.connections.mysql.username');
        $password = config('database.connections.mysql.password');

        try {
            $pdo = new \PDO(
                "mysql:host={$host};port={$port}",
                $username,
                $password,
                [\PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION]
            );

            foreach ($this->databases as $dbName => $description) {
                try {
                    $pdo->exec("CREATE DATABASE IF NOT EXISTS `{$dbName}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
                    $this->line("  ✓ {$dbName} - {$description}");
                } catch (\Exception $e) {
                    $this->error("  ✗ Erreur pour {$dbName}: " . $e->getMessage());
                }
            }

            $pdo = null; // Fermer la connexion
        } catch (\Exception $e) {
            $this->error("  ✗ Connexion impossible: " . $e->getMessage());
            return;
        }

        $this->newLine();
    }


    /**
     * Exécuter les migrations
     */
    private function runMigrations(): void
    {
        $this->info('🔄 Exécution des migrations...');

        $fresh = $this->option('fresh');

        // Migration principale (base par défaut)
        if ($fresh) {
            $this->line('  → Migration fresh sur la base principale...');
            Artisan::call('migrate:fresh', ['--force' => true]);
        } else {
            $this->line('  → Migration sur la base principale...');
            Artisan::call('migrate', ['--force' => true]);
        }
        $this->line('  ✓ Base principale migrée');

        // Migrations pour les tables multi-bases via Schema
        $this->createMultiDatabaseTables();

        $this->newLine();
    }

    /**
     * Créer les tables pour les bases supplémentaires
     */
    private function createMultiDatabaseTables(): void
    {
        $this->line('  → Création des tables multi-bases...');

        // Tables school_mp
        $this->createMPTables();

        // Tables school_college
        $this->createCollegeTables();

        // Tables school_lycee
        $this->createLyceeTables();

        $this->line('  ✓ Tables multi-bases créées');
    }

    /**
     * Tables Maternelle/Primaire
     */
    private function createMPTables(): void
    {
        $connection = 'school_mp';

        // Classes
        if (!Schema::connection($connection)->hasTable('classes_mp')) {
            Schema::connection($connection)->create('classes_mp', function ($table) {
                $table->uuid('id')->primary();
                $table->string('niveau', 10);
                $table->string('nom', 10);
                $table->string('cycle', 20)->default('primaire');
                $table->integer('capacite')->default(40);
                $table->uuid('teacher_id')->nullable();
                $table->string('salle', 50)->nullable();
                $table->boolean('active')->default(true);
                $table->timestamps();
                $table->unique(['niveau', 'nom']);
            });
        }

        // Élèves
        if (!Schema::connection($connection)->hasTable('students_mp')) {
            Schema::connection($connection)->create('students_mp', function ($table) {
                $table->uuid('id')->primary();
                $table->string('matricule', 30)->unique();
                $table->string('nom', 100);
                $table->string('prenoms', 150);
                $table->enum('sexe', ['M', 'F']);
                $table->date('date_naissance');
                $table->string('lieu_naissance', 100);
                $table->string('nationalite', 50)->default('Burkinabè');
                $table->string('photo_url', 255)->nullable();
                $table->string('statut', 20)->default('actif');
                $table->timestamps();
            });
        }

        // Enseignants
        if (!Schema::connection($connection)->hasTable('teachers_mp')) {
            Schema::connection($connection)->create('teachers_mp', function ($table) {
                $table->uuid('id')->primary();
                $table->string('matricule', 30)->unique();
                $table->string('nom', 100);
                $table->string('prenom', 100);
                $table->enum('sexe', ['M', 'F']);
                $table->string('telephone', 20)->nullable();
                $table->string('email', 100)->nullable();
                $table->string('specialite', 100)->nullable();
                $table->string('statut', 20)->default('titulaire');
                $table->uuid('user_id')->nullable();
                $table->timestamps();
            });
        }

        // Matières
        if (!Schema::connection($connection)->hasTable('subjects_mp')) {
            Schema::connection($connection)->create('subjects_mp', function ($table) {
                $table->uuid('id')->primary();
                $table->string('code', 20)->unique();
                $table->string('nom', 100);
                $table->integer('coefficient')->default(1);
                $table->integer('heures_semaine')->default(2);
                $table->timestamps();
            });
        }

        // Inscriptions
        if (!Schema::connection($connection)->hasTable('enrollments_mp')) {
            Schema::connection($connection)->create('enrollments_mp', function ($table) {
                $table->uuid('id')->primary();
                $table->uuid('student_id');
                $table->uuid('class_id');
                $table->uuid('school_year_id');
                $table->date('date_inscription');
                $table->string('statut', 20)->default('en_attente');
                $table->string('decision_finale', 50)->nullable();
                $table->timestamps();
                $table->unique(['student_id', 'school_year_id']);
            });
        }
    }

    /**
     * Tables Collège
     */
    private function createCollegeTables(): void
    {
        $connection = 'school_college';

        // Classes
        if (!Schema::connection($connection)->hasTable('classes_college')) {
            Schema::connection($connection)->create('classes_college', function ($table) {
                $table->uuid('id')->primary();
                $table->string('niveau', 10);
                $table->string('nom', 10);
                $table->integer('capacite')->default(50);
                $table->uuid('prof_principal_id')->nullable();
                $table->string('salle', 50)->nullable();
                $table->boolean('active')->default(true);
                $table->timestamps();
                $table->unique(['niveau', 'nom']);
            });
        }

        // Élèves
        if (!Schema::connection($connection)->hasTable('students_college')) {
            Schema::connection($connection)->create('students_college', function ($table) {
                $table->uuid('id')->primary();
                $table->string('matricule', 30)->unique();
                $table->string('nom', 100);
                $table->string('prenoms', 150);
                $table->enum('sexe', ['M', 'F']);
                $table->date('date_naissance');
                $table->string('lieu_naissance', 100);
                $table->string('nationalite', 50)->default('Burkinabè');
                $table->string('photo_url', 255)->nullable();
                $table->string('statut', 20)->default('actif');
                $table->timestamps();
            });
        }

        // Enseignants
        if (!Schema::connection($connection)->hasTable('teachers_college')) {
            Schema::connection($connection)->create('teachers_college', function ($table) {
                $table->uuid('id')->primary();
                $table->string('matricule', 30)->unique();
                $table->string('nom', 100);
                $table->string('prenom', 100);
                $table->enum('sexe', ['M', 'F']);
                $table->string('telephone', 20)->nullable();
                $table->string('email', 100)->nullable();
                $table->string('specialite', 100)->nullable();
                $table->string('statut', 20)->default('titulaire');
                $table->uuid('user_id')->nullable();
                $table->timestamps();
            });
        }

        // Matières
        if (!Schema::connection($connection)->hasTable('subjects_college')) {
            Schema::connection($connection)->create('subjects_college', function ($table) {
                $table->uuid('id')->primary();
                $table->string('code', 20)->unique();
                $table->string('nom', 100);
                $table->integer('coefficient')->default(1);
                $table->integer('heures_semaine')->default(2);
                $table->timestamps();
            });
        }

        // Inscriptions
        if (!Schema::connection($connection)->hasTable('enrollments_college')) {
            Schema::connection($connection)->create('enrollments_college', function ($table) {
                $table->uuid('id')->primary();
                $table->uuid('student_id');
                $table->uuid('class_id');
                $table->uuid('school_year_id');
                $table->date('date_inscription');
                $table->string('statut', 20)->default('en_attente');
                $table->string('decision_finale', 50)->nullable();
                $table->timestamps();
                $table->unique(['student_id', 'school_year_id']);
            });
        }
    }

    /**
     * Tables Lycée
     */
    private function createLyceeTables(): void
    {
        $connection = 'school_lycee';

        // Classes
        if (!Schema::connection($connection)->hasTable('classes_lycee')) {
            Schema::connection($connection)->create('classes_lycee', function ($table) {
                $table->uuid('id')->primary();
                $table->string('niveau', 10);
                $table->string('nom', 10);
                $table->string('serie', 5)->nullable();
                $table->integer('capacite')->default(50);
                $table->uuid('prof_principal_id')->nullable();
                $table->string('salle', 50)->nullable();
                $table->boolean('active')->default(true);
                $table->timestamps();
                $table->unique(['niveau', 'nom']);
            });
        }

        // Élèves
        if (!Schema::connection($connection)->hasTable('students_lycee')) {
            Schema::connection($connection)->create('students_lycee', function ($table) {
                $table->uuid('id')->primary();
                $table->string('matricule', 30)->unique();
                $table->string('nom', 100);
                $table->string('prenoms', 150);
                $table->enum('sexe', ['M', 'F']);
                $table->date('date_naissance');
                $table->string('lieu_naissance', 100);
                $table->string('nationalite', 50)->default('Burkinabè');
                $table->string('photo_url', 255)->nullable();
                $table->string('statut', 20)->default('actif');
                $table->timestamps();
            });
        }

        // Enseignants
        if (!Schema::connection($connection)->hasTable('teachers_lycee')) {
            Schema::connection($connection)->create('teachers_lycee', function ($table) {
                $table->uuid('id')->primary();
                $table->string('matricule', 30)->unique();
                $table->string('nom', 100);
                $table->string('prenom', 100);
                $table->enum('sexe', ['M', 'F']);
                $table->string('telephone', 20)->nullable();
                $table->string('email', 100)->nullable();
                $table->string('specialite', 100)->nullable();
                $table->string('statut', 20)->default('titulaire');
                $table->uuid('user_id')->nullable();
                $table->timestamps();
            });
        }

        // Matières Lycée (avec coefficients par série)
        if (!Schema::connection($connection)->hasTable('subjects_lycee')) {
            Schema::connection($connection)->create('subjects_lycee', function ($table) {
                $table->uuid('id')->primary();
                $table->string('code', 20)->unique();
                $table->string('nom', 100);
                $table->integer('coefficient_a')->default(1);
                $table->integer('coefficient_c')->default(1);
                $table->integer('coefficient_d')->default(1);
                $table->integer('heures_semaine')->default(2);
                $table->timestamps();
            });
        }

        // Inscriptions
        if (!Schema::connection($connection)->hasTable('enrollments_lycee')) {
            Schema::connection($connection)->create('enrollments_lycee', function ($table) {
                $table->uuid('id')->primary();
                $table->uuid('student_id');
                $table->uuid('class_id');
                $table->uuid('school_year_id');
                $table->date('date_inscription');
                $table->string('statut', 20)->default('en_attente');
                $table->string('decision_finale', 50)->nullable();
                $table->timestamps();
                $table->unique(['student_id', 'school_year_id']);
            });
        }
    }

    /**
     * Exécuter le seeder
     */
    private function runSeeder(): void
    {
        $this->newLine();
        $this->info('🌱 Exécution des seeders...');

        try {
            Artisan::call('db:seed', [
                '--class' => 'Database\\Seeders\\CompleteSchoolSeeder',
                '--force' => true
            ]);
            $this->line('  ✓ Données de démonstration insérées');
        } catch (\Exception $e) {
            $this->error('  ✗ Erreur: ' . $e->getMessage());
        }
    }
}
