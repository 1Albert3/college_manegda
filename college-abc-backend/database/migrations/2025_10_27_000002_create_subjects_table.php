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
        Schema::create('subjects', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name'); // ex: "Mathématiques", "Français"
            $table->string('code')->unique(); // ex: "MATH", "FRAN"
            $table->enum('category', [
                'sciences', 'literature', 'language', 'social_studies',
                'arts', 'physical_education', 'technology', 'other'
            ])->default('other');
            $table->text('description')->nullable();
            $table->integer('coefficients')->default(1); // Pour les notes
            $table->integer('weekly_hours')->nullable(); // Heures par semaine
            $table->enum('level_type', [
                'primary', 'secondary', 'both'
            ])->default('secondary'); // Applicable aux niveaux
            $table->boolean('is_active')->default(true);
            $table->json('program')->nullable(); // Programme pédagogique
            $table->timestamps();

            // Indexes
            $table->index('code');
            $table->index('category');
            $table->index('level_type');
            $table->index('is_active');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('subjects');
    }
};
