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
        Schema::create('evaluations', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name');
            $table->string('code')->unique();
            $table->text('description')->nullable();
            $table->enum('type', ['continuous', 'semester', 'annual']); // control continu, semestre, annuel
            $table->string('period'); // Trimestre 1, Trimestre 2, Année
            $table->integer('coefficient')->default(1);
            $table->decimal('weight_percentage', 5, 2)->default(100.00); // peso em %
            $table->foreignUuid('academic_year_id')->constrained('academic_years')->onDelete('cascade');
            $table->foreignUuid('subject_id')->constrained('subjects')->onDelete('cascade');
            $table->foreignUuid('class_id')->constrained('class_rooms')->onDelete('cascade');
            $table->foreignUuid('teacher_id')->constrained('users')->onDelete('cascade');
            $table->date('evaluation_date');
            $table->enum('status', ['planned', 'ongoing', 'completed', 'cancelled'])->default('planned');
            $table->decimal('maximum_score', 5, 2)->default(20.00);
            $table->decimal('minimum_score', 5, 2)->default(0.00);
            $table->json('grading_criteria')->nullable(); // critères d'évaluation
            $table->text('comments')->nullable();
            $table->timestamps();

            $table->index(['academic_year_id', 'subject_id']);
            $table->index(['class_id', 'evaluation_date']);
            $table->index(['status', 'evaluation_date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('evaluations');
    }
};
