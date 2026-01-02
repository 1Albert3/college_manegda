<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('grades')) Schema::create('grades', function (Blueprint $table) {
            $table->id();
            $table->foreignId('evaluation_id')->constrained()->onDelete('cascade');
            $table->foreignUuid('student_id')->constrained()->onDelete('cascade');
            $table->decimal('score', 5, 2);
            $table->decimal('weighted_score', 5, 2)->nullable(); // score × coefficient
            $table->text('comment')->nullable();
            $table->foreignUuid('graded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('graded_at')->nullable();
            $table->timestamps();

            $table->unique(['evaluation_id', 'student_id']);
            $table->index('student_id');
            $table->index('score');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('grades');
    }
};
