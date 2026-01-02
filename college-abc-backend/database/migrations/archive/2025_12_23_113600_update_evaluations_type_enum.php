<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Modify the enum to include all expected types
        DB::statement("ALTER TABLE evaluations MODIFY COLUMN type ENUM('continuous', 'semester', 'annual', 'test', 'exam', 'quiz', 'homework', 'participation') NOT NULL DEFAULT 'test'");
    }

    public function down(): void
    {
        // Revert to original enum values
        DB::statement("ALTER TABLE evaluations MODIFY COLUMN type ENUM('exam', 'test', 'quiz', 'homework', 'participation') NOT NULL DEFAULT 'test'");
    }
};
