<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class TestListMP extends Command
{
    protected $signature = 'test:list-mp';
    protected $description = 'List tables in school_mp';

    public function handle()
    {
        $this->info("Listing tables in school_mp connection:");
        $tables = DB::connection('school_mp')->select('SHOW TABLES');
        foreach ($tables as $table) {
            $t = (array)$table;
            $this->line("- " . array_values($t)[0]);
        }

        $this->info("\nChecking classes_mp existence via Schema:");
        if (Schema::connection('school_mp')->hasTable('classes_mp')) {
            $this->info("✅ classes_mp exists!");
        } else {
            $this->error("❌ classes_mp MISSING!");
        }
    }
}
