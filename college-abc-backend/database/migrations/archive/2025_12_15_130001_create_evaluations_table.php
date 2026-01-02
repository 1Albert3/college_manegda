<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('evaluations')) Schema::create('evaluations', function (Blueprint $table) {
            $table->id();
            $table->foreignUuid('subject_id')->constrained()->onDelete('cascade');
            $table->foreignUuid('class_room_id')->constrained()->onDelete('cascade');
            $table->foreignUuid('teacher_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('semester_id')->nullable()->constrained()->onDelete('set null');
            $table->foreignUuid('academic_year_id')->constrained()->onDelete('cascade');
            $table->string('title');
            $table->enum('type', ['exam', 'test', 'quiz', 'homework', 'participation']);
            $table->decimal('max_score', 5, 2)->default(20.00);
            $table->decimal('coefficient', 3, 2)->default(1.00);
            $table->date('date');
            $table->text('description')->nullable();
            $table->timestamps();

            $table->index(['class_room_id', 'subject_id']);
            $table->index('date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('evaluations');
    }
};
