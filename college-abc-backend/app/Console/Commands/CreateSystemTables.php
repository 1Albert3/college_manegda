<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class CreateSystemTables extends Command
{
    protected $signature = 'db:create-system-tables';
    protected $description = 'Créer les tables système nécessaires dans school_core';

    public function handle()
    {
        $this->info('🔧 CRÉATION DES TABLES SYSTÈME');
        $this->info('==============================');

        $connection = 'school_core';

        // 1. Table personal_access_tokens (pour Sanctum)
        $this->info("\n1. Création de personal_access_tokens...");
        try {
            DB::connection($connection)->statement("
                CREATE TABLE IF NOT EXISTS personal_access_tokens (
                    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                    tokenable_type VARCHAR(255) NOT NULL,
                    tokenable_id BIGINT UNSIGNED NOT NULL,
                    name VARCHAR(255) NOT NULL,
                    token VARCHAR(64) NOT NULL UNIQUE,
                    abilities TEXT,
                    last_used_at TIMESTAMP NULL,
                    expires_at TIMESTAMP NULL,
                    created_at TIMESTAMP NULL,
                    updated_at TIMESTAMP NULL,
                    INDEX personal_access_tokens_tokenable_type_tokenable_id_index (tokenable_type, tokenable_id)
                )
            ");
            $this->info("   ✅ Table personal_access_tokens créée");
        } catch (\Exception $e) {
            $this->error("   ❌ Erreur: " . $e->getMessage());
        }

        // 2. Table cache (optionnelle)
        $this->info("\n2. Création de cache...");
        try {
            DB::connection($connection)->statement("
                CREATE TABLE IF NOT EXISTS cache (
                    `key` VARCHAR(255) NOT NULL PRIMARY KEY,
                    `value` MEDIUMTEXT NOT NULL,
                    `expiration` INT NOT NULL
                )
            ");
            $this->info("   ✅ Table cache créée");
        } catch (\Exception $e) {
            $this->error("   ❌ Erreur: " . $e->getMessage());
        }

        // 3. Table cache_locks
        $this->info("\n3. Création de cache_locks...");
        try {
            DB::connection($connection)->statement("
                CREATE TABLE IF NOT EXISTS cache_locks (
                    `key` VARCHAR(255) NOT NULL PRIMARY KEY,
                    `owner` VARCHAR(255) NOT NULL,
                    `expiration` INT NOT NULL
                )
            ");
            $this->info("   ✅ Table cache_locks créée");
        } catch (\Exception $e) {
            $this->error("   ❌ Erreur: " . $e->getMessage());
        }

        // 4. Table sessions
        $this->info("\n4. Création de sessions...");
        try {
            DB::connection($connection)->statement("
                CREATE TABLE IF NOT EXISTS sessions (
                    id VARCHAR(255) NOT NULL PRIMARY KEY,
                    user_id BIGINT UNSIGNED NULL,
                    ip_address VARCHAR(45) NULL,
                    user_agent TEXT NULL,
                    payload LONGTEXT NOT NULL,
                    last_activity INT NOT NULL,
                    INDEX sessions_user_id_index (user_id),
                    INDEX sessions_last_activity_index (last_activity)
                )
            ");
            $this->info("   ✅ Table sessions créée");
        } catch (\Exception $e) {
            $this->error("   ❌ Erreur: " . $e->getMessage());
        }

        $this->info("\n✅ Tables système créées avec succès!");
        $this->info("\n🎯 Vous pouvez maintenant tester la connexion:");
        $this->info("curl -X POST http://localhost:8000/api/auth/login \\");
        $this->info("  -H \"Content-Type: application/json\" \\");
        $this->info("  -d '{\"email\":\"admin@college-abc.bf\",\"password\":\"password123\",\"role\":\"admin\"}'");
    }
}