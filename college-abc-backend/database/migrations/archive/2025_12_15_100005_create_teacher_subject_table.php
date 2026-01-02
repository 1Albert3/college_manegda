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
        if (!Schema::hasTable('teacher_subject')) Schema::create('teacher_subject', function (Blueprint $table) {
            $table->id();
            $table->foreignUuid('user_id')->constrained()->onDelete('cascade'); // Teacher
            $table->foreignUuid('subject_id')->constrained()->onDelete('cascade');
            $table->foreignUuid('class_room_id')->nullable()->constrained('class_rooms')->onDelete('cascade'); // Si spécifique à une classe
            $table->foreignUuid('academic_year_id')->constrained()->onDelete('cascade');
            $table->boolean('is_main_teacher')->default(false); // Professeur principal de la matière
            $table->timestamps();

            // Indexes
            $table->index('user_id');
            $table->index('subject_id');
            $table->index('class_room_id');
            $table->index('academic_year_id');
            $table->index(['user_id', 'subject_id', 'academic_year_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('teacher_subject');
    }
};
