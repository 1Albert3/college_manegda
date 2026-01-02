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
        if (!Schema::hasTable('class_subject')) Schema::create('class_subject', function (Blueprint $table) {
            $table->id();
            $table->foreignUuid('class_room_id')->constrained('class_rooms')->onDelete('cascade');
            $table->foreignUuid('subject_id')->constrained()->onDelete('cascade');
            $table->integer('hours_per_week')->default(1); // Nombre d'heures par semaine
            $table->integer('coefficient')->nullable(); // Coefficient spécifique à la classe (peut override celui de la matière)
            $table->timestamps();

            // Unique constraint
            $table->unique(['class_room_id', 'subject_id']);

            // Indexes
            $table->index('class_room_id');
            $table->index('subject_id');
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
