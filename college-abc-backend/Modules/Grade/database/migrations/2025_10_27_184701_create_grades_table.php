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
        Schema::create('grades', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('student_id')->constrained('students')->onDelete('cascade');
            $table->foreignUuid('evaluation_id')->constrained('evaluations')->onDelete('cascade');
            $table->decimal('score', 5, 2); // note obtenue
            $table->decimal('coefficient', 3, 2)->default(1.00);
            $table->decimal('weighted_score', 5, 2); // note pondérée
            $table->enum('grade_letter', ['A+', 'A', 'B+', 'B', 'C+', 'C', 'D+', 'D', 'F'])->nullable(); // lettre
            $table->boolean('is_absent')->default(false);
            $table->text('comments')->nullable();
            $table->timestamp('recorded_at')->useCurrent();
            $table->foreignUuid('recorded_by')->constrained('users')->onDelete('cascade'); // qui a enregistré la note
            $table->timestamps();

            // Indexes for performance
            $table->unique(['student_id', 'evaluation_id']); // une seule note par élève par évaluation
            $table->index(['evaluation_id', 'student_id']);
            $table->index(['student_id', 'recorded_at']);
            $table->index('grade_letter');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('grades');
    }
};
