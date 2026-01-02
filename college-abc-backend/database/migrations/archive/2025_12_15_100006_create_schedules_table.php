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
        if (!Schema::hasTable('schedules')) Schema::create('schedules', function (Blueprint $table) {
            $table->id();
            $table->foreignUuid('class_room_id')->constrained('class_rooms')->onDelete('cascade');
            $table->foreignUuid('subject_id')->constrained()->onDelete('cascade');
            $table->foreignUuid('teacher_id')->constrained('users')->onDelete('cascade');
            $table->foreignUuid('academic_year_id')->constrained()->onDelete('cascade');
            $table->enum('day_of_week', ['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday']);
            $table->time('start_time');
            $table->time('end_time');
            $table->string('room')->nullable(); // Salle de classe ou laboratoire
            $table->text('notes')->nullable();
            $table->timestamps();

            // Indexes
            $table->index('class_room_id');
            $table->index('subject_id');
            $table->index('teacher_id');
            $table->index('academic_year_id');
            $table->index('day_of_week');
            $table->index(['class_room_id', 'day_of_week']);
            $table->index(['teacher_id', 'day_of_week']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('schedules');
    }
};
