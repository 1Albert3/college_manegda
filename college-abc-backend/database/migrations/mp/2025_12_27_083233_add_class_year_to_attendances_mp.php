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
        Schema::connection('school_mp')->table('attendances_mp', function (Blueprint $table) {
            if (!Schema::connection('school_mp')->hasColumn('attendances_mp', 'class_id')) {
                $table->uuid('class_id')->after('student_id')->index()->nullable();
            }
            if (!Schema::connection('school_mp')->hasColumn('attendances_mp', 'school_year_id')) {
                $table->uuid('school_year_id')->after('class_id')->index()->nullable();
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::connection('school_mp')->table('attendances_mp', function (Blueprint $table) {
            $table->dropColumn(['class_id', 'school_year_id']);
        });
    }
};
