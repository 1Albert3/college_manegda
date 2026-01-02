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
        Schema::table('students', function (Blueprint $table) {
            // Add missing columns
            $table->string('email')->nullable()->after('gender');
            $table->string('phone')->nullable()->after('email');
            $table->string('emergency_contact')->nullable()->after('phone');
            $table->string('emergency_phone')->nullable()->after('emergency_contact');
            $table->text('medical_conditions')->nullable()->after('blood_group');
            $table->text('allergies')->nullable()->after('medical_conditions');
            $table->string('nationality')->default('Burkinabé')->after('place_of_birth');
            $table->string('religion')->nullable()->after('nationality');
            $table->enum('status', ['active', 'graduated', 'transferred', 'expelled', 'withdrawn'])->default('active')->after('photo_path');
            $table->text('notes')->nullable()->after('status');
            $table->softDeletes();

            // Add indexes
            $table->index('matricule');
            $table->index('status');
            $table->index(['last_name', 'first_name']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('students', function (Blueprint $table) {
            $table->dropSoftDeletes();
            $table->dropIndex(['students_matricule_index']);
            $table->dropIndex(['students_status_index']);
            $table->dropIndex(['students_last_name_first_name_index']);
            $table->dropColumn([
                'email', 'phone', 'emergency_contact', 'emergency_phone',
                'medical_conditions', 'allergies', 'nationality', 'religion',
                'status', 'notes'
            ]);
        });
    }
};
