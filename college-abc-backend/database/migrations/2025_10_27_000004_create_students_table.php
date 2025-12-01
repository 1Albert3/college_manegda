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
        Schema::create('students', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('user_id')->constrained()->cascadeOnDelete();
            $table->string('matricule')->unique();
            $table->string('first_name');
            $table->string('last_name');
            $table->date('date_of_birth');
            $table->enum('gender', ['M', 'F']);
            $table->string('place_of_birth')->nullable();
            $table->text('address')->nullable();
            $table->string('photo')->nullable();
            $table->enum('status', ['active', 'suspended', 'graduated', 'withdrawn'])->default('active');
            $table->json('medical_info')->nullable();
            $table->timestamps();
            $table->softDeletes();

            // Indexes
            $table->index('matricule');
            $table->index('status');
        });

        // Table pivot parents-élèves
        Schema::create('parent_student', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('parent_id')->constrained('users')->cascadeOnDelete();
            $table->foreignUuid('student_id')->constrained()->cascadeOnDelete();
            $table->enum('relationship', ['father', 'mother', 'guardian', 'other']);
            $table->boolean('is_primary')->default(false);
            $table->timestamps();

            $table->unique(['parent_id', 'student_id']);
        });

        // Inscriptions
        Schema::create('enrollments', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('student_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('academic_year_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('class_id')->constrained('class_rooms')->cascadeOnDelete();
            $table->date('enrollment_date');
            $table->enum('status', ['pending', 'active', 'completed'])->default('pending');
            $table->decimal('discount_percentage', 5, 2)->default(0);
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->unique(['student_id', 'academic_year_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('enrollments');
        Schema::dropIfExists('parent_student');
        Schema::dropIfExists('students');
    }
};
