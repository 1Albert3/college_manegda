<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class CheckDatabases extends Command
{
    protected $signature = 'db:check';
    protected $description = 'Vérifier les bases de données et utilisateurs';

    public function handle()
    {
        $this->info('🔍 VÉRIFICATION DES BASES DE DONNÉES');
        $this->info('====================================');

        // 1. Connexion par défaut
        $this->info("\n1. Connexion par défaut:");
        try {
            $defaultDb = DB::connection()->getDatabaseName();
            $this->info("✅ Connecté à: $defaultDb");
        } catch (\Exception $e) {
            $this->error("❌ Erreur: " . $e->getMessage());
        }

        // 2. Bases disponibles
        $this->info("\n2. Bases de données disponibles:");
        try {
            $databases = DB::select('SHOW DATABASES');
            foreach ($databases as $db) {
                $this->line("   - {$db->Database}");
            }
        } catch (\Exception $e) {
            $this->error("❌ Erreur: " . $e->getMessage());
        }

        // 3. Utilisateurs dans la base par défaut
        $this->info("\n3. Utilisateurs dans la base par défaut:");
        try {
            $users = DB::table('users')->select('id', 'name', 'email', 'role', 'is_active')->get();
            if ($users->count() > 0) {
                foreach ($users as $user) {
                    $status = $user->is_active ? '✅' : '❌';
                    $this->line("   $status {$user->email} ({$user->role}) - {$user->name}");
                }
            } else {
                $this->warn("   ⚠️ Aucun utilisateur trouvé");
            }
        } catch (\Exception $e) {
            $this->error("❌ Erreur: " . $e->getMessage());
        }

        // 4. Autres connexions
        $connections = ['school_core', 'school_mp', 'school_college', 'school_lycee'];
        $this->info("\n4. Test des autres connexions:");

        foreach ($connections as $conn) {
            try {
                $dbName = DB::connection($conn)->getDatabaseName();
                $this->info("   ✅ $conn: Connecté à $dbName");
                
                try {
                    $userCount = DB::connection($conn)->table('users')->count();
                    $this->line("      → $userCount utilisateur(s)");
                } catch (\Exception $e) {
                    $this->line("      → Table users non trouvée");
                }
            } catch (\Exception $e) {
                $this->error("   ❌ $conn: " . $e->getMessage());
            }
        }

        $this->info("\n✅ Vérification terminée");
    }
}