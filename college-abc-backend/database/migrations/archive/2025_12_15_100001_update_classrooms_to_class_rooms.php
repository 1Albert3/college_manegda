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
        // Rename classrooms to class_rooms for consistency if it exists
        if (Schema::hasTable('classrooms') && !Schema::hasTable('class_rooms')) {
            Schema::rename('classrooms', 'class_rooms');
        }

        // Add missing columns if class_rooms exists
        if (Schema::hasTable('class_rooms')) {
            Schema::table('class_rooms', function (Blueprint $table) {
                if (!Schema::hasColumn('class_rooms', 'level_id')) {
                    $table->foreignUuid('level_id')->nullable()->constrained('levels')->onDelete('cascade');
                }
                if (!Schema::hasColumn('class_rooms', 'academic_year_id')) {
                    $table->foreignUuid('academic_year_id')->nullable()->constrained()->onDelete('cascade');
                }
                if (!Schema::hasColumn('class_rooms', 'main_teacher_id')) {
                    $table->foreignUuid('main_teacher_id')->nullable()->constrained('users')->onDelete('set null');
                }
                if (!Schema::hasColumn('class_rooms', 'room_number')) {
                    $table->string('room_number')->nullable();
                }
                if (!Schema::hasColumn('class_rooms', 'description')) {
                    $table->text('description')->nullable();
                }
                if (!Schema::hasColumn('class_rooms', 'is_active')) {
                    $table->boolean('is_active')->default(true);
                }
                if (!Schema::hasColumn('class_rooms', 'deleted_at')) {
                    $table->softDeletes();
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('class_rooms')) {
            Schema::table('class_rooms', function (Blueprint $table) {
                if (Schema::hasColumn('class_rooms', 'deleted_at')) $table->dropSoftDeletes();
                if (Schema::hasColumn('class_rooms', 'academic_year_id')) $table->dropForeign(['academic_year_id']);
                if (Schema::hasColumn('class_rooms', 'main_teacher_id')) $table->dropForeign(['main_teacher_id']);
                // Drop other columns if needed
            });
        }
    }
};
