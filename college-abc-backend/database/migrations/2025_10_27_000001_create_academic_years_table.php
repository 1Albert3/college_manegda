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
        Schema::create('academic_years', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name'); // ex: "2024-2025"
            $table->date('start_date');
            $table->date('end_date');
            $table->enum('status', ['planned', 'active', 'completed', 'cancelled'])->default('planned');
            $table->boolean('is_current')->default(false);
            $table->text('description')->nullable();
            $table->json('semesters')->nullable(); // Configuration des semestres
            $table->timestamps();

            // Indexes
            $table->index('status');
            $table->index('is_current');
            $table->index('start_date');
            $table->index('end_date');

            // Remove strict constraint - business logic handles current year uniqueness
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('academic_years');
    }
};
