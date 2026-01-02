<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class FullDemoSeeder extends Seeder
{
    /**
     * Seed the application with all demo data.
     */
    public function run(): void
    {
        $this->command->info('🚀 Démarrage de l\'injection des données de démonstration...');

        // Désactiver les contraintes pour le nettoyage
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');

        // Nettoyage par table (ordre inverse des dépendances)
        DB::table('payments')->delete();
        DB::table('invoices')->delete();
        DB::table('fee_structures')->delete();
        DB::table('book_loans')->delete();
        DB::table('books')->delete();
        DB::table('schedules')->delete();
        DB::table('subjects')->delete();

        DB::statement('SET FOREIGN_KEY_CHECKS=1;');

        $this->call([
            // 1. Staff & Utilisateurs
            StaffDemoSeeder::class,

            // 2. Finance (Frais, Factures, Paiements)
            FinanceDemoSeeder::class,

            // 3. Emplois du Temps (Matières, Créneaux)
            ScheduleDemoSeeder::class,

            // 4. Librairie (Livres, Emprunts)
            LibraryDemoSeeder::class,
        ]);

        $this->command->info('✨ Toutes les données de démonstration ont été injectées avec succès !');
        $this->command->info('🔑 Les nouveaux comptes staff utilisent le mot de passe: "password"');
    }
}
