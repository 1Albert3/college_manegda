<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\DatabaseService;
use Illuminate\Support\Facades\DB;

class SetupDatabases extends Command
{
    protected $signature = 'db:setup-multi';
    protected $description = 'Configurer les bases de données multiples';

    public function handle()
    {
        $this->info('🗄️ CONFIGURATION DES BASES DE DONNÉES MULTIPLES');
        $this->info('================================================');

        $dbService = new DatabaseService();

        // 1. Vérifier l'état actuel
        $this->info("\n1. État actuel des bases:");
        $status = $dbService->checkDatabasesStatus();
        
        foreach ($status as $conn => $info) {
            if ($info['connected']) {
                $this->info("   ✅ $conn: {$info['database']} ({$info['users_count']} utilisateurs)");
            } else {
                $this->error("   ❌ $conn: {$info['error']}");
            }
        }

        // 2. Créer les tables users manquantes
        $this->info("\n2. Création des tables users:");
        $results = $dbService->createUsersTables();
        
        foreach ($results as $conn => $result) {
            $this->line("   $conn: $result");
        }

        // 3. Migrer les utilisateurs vers school_core
        $this->info("\n3. Migration des utilisateurs vers school_core:");
        
        $count = $dbService->migrateUsersToCorrectDatabases();
        $this->info("   ✅ $count utilisateurs migrés vers school_core");

        // 4. Configurer la base par défaut
        $this->info("\n4. Configuration de la base par défaut:");
        
        $this->updateEnvFile();
        $this->info("   ✅ Fichier .env mis à jour");
        $this->warn("   ⚠️ Redémarrez le serveur pour appliquer les changements");

        // 5. Résumé final
        $this->info("\n📋 CONFIGURATION FINALE:");
        $this->info("- school_core: Authentification, administration");
        $this->info("- school_mp: Maternelle/Primaire (CP, CE1, CE2, CM1, CM2)");
        $this->info("- school_college: Collège (6ème, 5ème, 4ème, 3ème)");
        $this->info("- school_lycee: Lycée (2nde, 1ère, Tle)");

        $this->info("\n✅ Configuration terminée!");
    }

    private function updateEnvFile()
    {
        $envPath = base_path('.env');
        $envContent = file_get_contents($envPath);
        
        // Remplacer la base par défaut
        $envContent = preg_replace(
            '/^DB_DATABASE=.*$/m',
            'DB_DATABASE=school_core',
            $envContent
        );
        
        // Ajouter les configurations des autres bases si elles n'existent pas
        if (!str_contains($envContent, 'DB_DATABASE_CORE')) {
            $envContent .= "\n# Bases de données multiples\n";
            $envContent .= "DB_DATABASE_CORE=school_core\n";
            $envContent .= "DB_DATABASE_MP=school_maternelle_primaire\n";
            $envContent .= "DB_DATABASE_COLLEGE=school_college\n";
            $envContent .= "DB_DATABASE_LYCEE=school_lycee\n";
        }
        
        file_put_contents($envPath, $envContent);
    }
}