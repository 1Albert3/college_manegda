<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class MigrateAll extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:migrate-all';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Exécute toutes les migrations sur toutes les bases de données (Zéro Blocage)';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('🚀 Démarrage des migrations granulaires (Zéro Blocage)...');

        $configs = [
            ['db' => 'school_core', 'path' => 'database/migrations'],
            ['db' => 'school_core', 'path' => 'database/migrations/core'],
            ['db' => 'school_mp', 'path' => 'database/migrations/mp'],
            ['db' => 'school_college', 'path' => 'database/migrations/college'],
            ['db' => 'school_lycee', 'path' => 'database/migrations/lycee'],
        ];

        foreach ($configs as $config) {
            $this->info("--------------------------------------------------");
            $this->info("📂 Dossier: " . $config['path'] . " -> BD: " . $config['db']);

            $files = glob(base_path($config['path'] . '/*.php'));
            if (!$files) continue;
            sort($files);

            foreach ($files as $file) {
                $relative = str_replace(base_path() . DIRECTORY_SEPARATOR, '', $file);
                $this->comment("   📝 Migration: " . basename($file));

                try {
                    // Utilisation de callSilent pour éviter de polluer la sortie, on gère les messages nous-mêmes
                    $this->callSilent('migrate', [
                        '--database' => $config['db'],
                        '--path' => $relative,
                        '--force' => true,
                    ]);
                } catch (\Exception $e) {
                    // On logue l'erreur mais on continue le processus
                    $this->warn("      ⚠️  Passé: " . $e->getMessage());
                }
            }
        }

        $this->info('--------------------------------------------------');
        $this->info('✅ Processus de migration terminé !');
    }
}
