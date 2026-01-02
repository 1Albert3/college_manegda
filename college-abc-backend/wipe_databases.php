<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

$connections = ['school_core', 'school_mp', 'school_college', 'school_lycee'];

foreach ($connections as $conn) {
    echo "Cleaning connection: $conn...\n";
    try {
        $tables = DB::connection($conn)->select('SHOW TABLES');
        $key = "Tables_in_" . config("database.connections.$conn.database");

        Schema::connection($conn)->disableForeignKeyConstraints();
        foreach ($tables as $table) {
            $tableName = $table->$key;
            echo "Dropping table: $tableName\n";
            Schema::connection($conn)->drop($tableName);
        }
        Schema::connection($conn)->enableForeignKeyConstraints();
    } catch (\Exception $e) {
        echo "Error on $conn: " . $e->getMessage() . "\n";
    }
}
echo "All done!\n";
