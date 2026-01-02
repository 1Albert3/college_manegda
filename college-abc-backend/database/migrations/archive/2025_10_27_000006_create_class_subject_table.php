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
        // Table pivot class_subject (classes-matières)
        if (!Schema::hasTable('class_subject')) Schema::create('class_subject', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('class_id')->constrained('class_rooms')->onDelete('cascade');
            $table->foreignUuid('subject_id')->constrained()->onDelete('cascade');
            $table->foreignUuid('academic_year_id')->constrained()->onDelete('cascade');
            $table->integer('weekly_hours')->default(1);
            $table->integer('coefficient')->default(1);
            $table->timestamps();

            $table->unique(['class_id', 'subject_id', 'academic_year_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('class_subject');
    }
};
