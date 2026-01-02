<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (Schema::connection('school_core')->hasTable('users')) {
            Schema::connection('school_core')->table('users', function (Blueprint $table) {
                if (!Schema::connection('school_core')->hasColumn('users', 'matricule')) {
                    $table->string('matricule')->nullable()->unique()->after('email');
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::connection('school_core')->hasTable('users')) {
            Schema::connection('school_core')->table('users', function (Blueprint $table) {
                if (Schema::connection('school_core')->hasColumn('users', 'matricule')) {
                    $table->dropColumn('matricule');
                }
            });
        }
    }
};
