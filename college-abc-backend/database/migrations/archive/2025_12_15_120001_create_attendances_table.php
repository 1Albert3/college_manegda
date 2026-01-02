<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('attendances')) Schema::create('attendances', function (Blueprint $table) {
            $table->id();
            $table->foreignUuid('student_id')->constrained()->onDelete('cascade');
            $table->foreignUuid('class_room_id')->constrained()->onDelete('cascade');
            $table->foreignUuid('academic_year_id')->constrained()->onDelete('cascade');
            $table->date('date');
            $table->enum('status', ['present', 'absent', 'late', 'excused'])->default('present');
            $table->time('check_in_time')->nullable();
            $table->time('expected_time')->default('08:00:00');
            $table->integer('late_minutes')->default(0);
            $table->foreignUuid('marked_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('notes')->nullable();
            $table->timestamps();

            // Unique constraint: one attendance per student per day
            $table->unique(['student_id', 'date']);

            // Indexes
            $table->index('date');
            $table->index('status');
            $table->index(['class_room_id', 'date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('attendances');
    }
};
