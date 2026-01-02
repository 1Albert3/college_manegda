<?php

/**
 * Clean All Databases Script
 * Truncates all tables in school_mp, school_college, school_lycee
 */

require_once __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;

$connections = ['school_mp', 'school_college', 'school_lycee'];

foreach ($connections as $conn) {
    echo "Cleaning $conn...\n";

    try {
        DB::connection($conn)->statement('SET FOREIGN_KEY_CHECKS=0');

        $tables = DB::connection($conn)->select('SHOW TABLES');

        foreach ($tables as $table) {
            $tableName = array_values((array)$table)[0];
            if ($tableName !== 'migrations') {
                DB::connection($conn)->table($tableName)->truncate();
                echo "  Truncated: $tableName\n";
            }
        }

        DB::connection($conn)->statement('SET FOREIGN_KEY_CHECKS=1');
        echo "  ✓ $conn cleaned\n";
    } catch (Exception $e) {
        echo "  Error: " . $e->getMessage() . "\n";
    }
}

echo "\n✅ All databases cleaned!\n";
