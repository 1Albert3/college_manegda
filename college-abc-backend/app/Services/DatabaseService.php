<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;

class DatabaseService
{
    /**
     * Mapping des niveaux vers les bases de données
     */
    private array $levelToDatabase = [
        // Maternelle/Primaire
        'maternelle' => 'school_mp',
        'cp' => 'school_mp',
        'ce1' => 'school_mp',
        'ce2' => 'school_mp',
        'cm1' => 'school_mp',
        'cm2' => 'school_mp',
        
        // Collège
        '6eme' => 'school_college',
        '5eme' => 'school_college',
        '4eme' => 'school_college',
        '3eme' => 'school_college',
        
        // Lycée
        '2nde' => 'school_lycee',
        '1ere' => 'school_lycee',
        'tle' => 'school_lycee',
    ];

    /**
     * Obtenir la connexion pour un niveau
     */
    public function getConnectionForLevel(string $level): string
    {
        $normalizedLevel = strtolower(str_replace(['è', 'ème'], 'eme', $level));
        return $this->levelToDatabase[$normalizedLevel] ?? 'school_core';
    }

    /**
     * Obtenir la connexion pour un utilisateur selon son rôle
     */
    public function getConnectionForUser($user): string
    {
        // Les admins, secrétaires, comptables utilisent la base centrale
        if (in_array($user->role, ['admin', 'super_admin', 'director', 'secretary', 'accountant'])) {
            return 'school_core';
        }
        
        // Pour les enseignants et élèves, on détermine selon leur affectation
        // Par défaut, on utilise la base centrale pour l'authentification
        return 'school_core';
    }

    /**
     * Migrer les utilisateurs vers les bonnes bases
     */
    public function migrateUsersToCorrectDatabases()
    {
        // 1. Copier tous les utilisateurs vers school_core
        $users = DB::connection('mysql')->table('users')->get();
        
        foreach ($users as $user) {
            // Adapter les champs selon la structure de school_core
            $userData = [
                'id' => $user->id,
                'email' => $user->email,
                'password' => $user->password,
                'phone' => $user->phone ?? null,
                'first_name' => $this->extractFirstName($user->name ?? ''),
                'last_name' => $this->extractLastName($user->name ?? ''),
                'profile_photo' => $user->profile_photo ?? null,
                'email_verified_at' => $user->email_verified_at,
                'is_active' => $user->is_active ?? 1,
                'last_login_at' => $user->last_login_at,
                'role' => $user->role,
                'remember_token' => $user->remember_token,
                'created_at' => $user->created_at,
                'updated_at' => $user->updated_at,
                'deleted_at' => $user->deleted_at ?? null,
            ];
            
            DB::connection('school_core')->table('users')->updateOrInsert(
                ['email' => $user->email],
                $userData
            );
        }
        
        return count($users);
    }
    
    private function extractFirstName(string $fullName): string
    {
        $parts = explode(' ', trim($fullName));
        return $parts[0] ?? '';
    }
    
    private function extractLastName(string $fullName): string
    {
        $parts = explode(' ', trim($fullName));
        array_shift($parts); // Enlever le prénom
        return implode(' ', $parts);
    }

    /**
     * Vérifier l'état des bases de données
     */
    public function checkDatabasesStatus(): array
    {
        $status = [];
        $connections = ['school_core', 'school_mp', 'school_college', 'school_lycee'];
        
        foreach ($connections as $conn) {
            try {
                $dbName = DB::connection($conn)->getDatabaseName();
                $userCount = 0;
                
                try {
                    $userCount = DB::connection($conn)->table('users')->count();
                } catch (\Exception $e) {
                    // Table users n'existe pas
                }
                
                $status[$conn] = [
                    'connected' => true,
                    'database' => $dbName,
                    'users_count' => $userCount,
                    'has_users_table' => $userCount >= 0
                ];
            } catch (\Exception $e) {
                $status[$conn] = [
                    'connected' => false,
                    'error' => $e->getMessage()
                ];
            }
        }
        
        return $status;
    }

    /**
     * Créer les tables users dans toutes les bases
     */
    public function createUsersTables()
    {
        $connections = ['school_core', 'school_mp', 'school_college', 'school_lycee'];
        $results = [];
        
        foreach ($connections as $conn) {
            try {
                // Vérifier si la table existe
                $exists = DB::connection($conn)->getSchemaBuilder()->hasTable('users');
                
                if (!$exists) {
                    // Créer la table users
                    DB::connection($conn)->statement("
                        CREATE TABLE users (
                            id VARCHAR(36) PRIMARY KEY,
                            name VARCHAR(255) NOT NULL,
                            email VARCHAR(255) UNIQUE NOT NULL,
                            email_verified_at TIMESTAMP NULL,
                            password VARCHAR(255) NOT NULL,
                            phone VARCHAR(20) NULL,
                            role_type VARCHAR(50) NULL,
                            is_active BOOLEAN DEFAULT 1,
                            last_login_at TIMESTAMP NULL,
                            remember_token VARCHAR(100) NULL,
                            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                            profile_type VARCHAR(50) NULL,
                            profile_id VARCHAR(36) NULL,
                            role VARCHAR(50) NOT NULL,
                            deleted_at TIMESTAMP NULL
                        )
                    ");
                    $results[$conn] = 'Table créée';
                } else {
                    $results[$conn] = 'Table existe déjà';
                }
            } catch (\Exception $e) {
                $results[$conn] = 'Erreur: ' . $e->getMessage();
            }
        }
        
        return $results;
    }
}