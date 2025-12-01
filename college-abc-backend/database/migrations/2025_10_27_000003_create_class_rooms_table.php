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
        Schema::create('class_rooms', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name'); // ex: "6ème A", "Terminale S"
            $table->string('level'); // ex: "6ème", "5ème", "4ème"
            $table->string('stream')->nullable(); // ex: "Sciences", "Littéraire", "Économique"
            $table->integer('capacity')->nullable(); // Capacité maximale
            $table->integer('current_students_count')->default(0);
            $table->enum('status', ['active', 'inactive', 'archived'])->default('active');
            $table->text('description')->nullable();
            $table->json('timetable')->nullable(); // Emploi du temps par défaut
            $table->timestamps();

            // Indexes
            $table->index('level');
            $table->index('stream');
            $table->index('status');
            $table->index(['level', 'stream']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('class_rooms');
    }
};
