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
        $tableNames = config('permission.table_names');
        
        if (empty($tableNames)) {
             // Fallback default
             $tableNames = [
                 'model_has_permissions' => 'model_has_permissions',
                 'model_has_roles' => 'model_has_roles',
             ];
        }

        Schema::table($tableNames['model_has_permissions'], function (Blueprint $table) {
            $table->string('model_id', 36)->change();
        });

        Schema::table($tableNames['model_has_roles'], function (Blueprint $table) {
            $table->string('model_id', 36)->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $tableNames = config('permission.table_names');

        Schema::table($tableNames['model_has_permissions'], function (Blueprint $table) {
            $table->unsignedBigInteger('model_id')->change();
        });

        Schema::table($tableNames['model_has_roles'], function (Blueprint $table) {
            $table->unsignedBigInteger('model_id')->change();
        });
    }
};
