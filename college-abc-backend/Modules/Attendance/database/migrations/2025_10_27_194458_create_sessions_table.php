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
        Schema::create('class_sessions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name'); // "Mathématiques - Classe 1A - Lundi 8h-9h"
            $table->text('description')->nullable();

            // Planning de la séance
            $table->foreignUuid('academic_year_id')->constrained('academic_years')->onDelete('cascade');
            $table->foreignUuid('subject_id')->constrained('subjects')->onDelete('cascade');
            $table->foreignUuid('class_id')->constrained('class_rooms')->onDelete('cascade');
            $table->foreignUuid('teacher_id')->constrained('users')->onDelete('cascade');

            $table->date('session_date'); // Date de la séance
            $table->time('start_time'); // Heure de début
            $table->time('end_time'); // Heure de fin

            $table->integer('duration_minutes'); // Durée calculée
            $table->enum('type', ['regular', 'exam', 'practical', 'special'])->default('regular');

            // Statut et exécution
            $table->enum('status', ['scheduled', 'in_progress', 'completed', 'cancelled'])->default('scheduled');
            $table->timestamp('actual_start_time')->nullable();
            $table->timestamp('actual_end_time')->nullable();

            // Salle et localisation
            $table->string('room')->nullable(); // Salle spécifique (override class room)
            $table->text('location_details')->nullable();

            // Contenu pédagogique
            $table->string('topic')->nullable(); // Thème du cours
            $table->text('objectives')->nullable(); // Objectifs d'apprentissage
            $table->text('materials')->nullable(); // Matériel nécessaire
            $table->text('homework')->nullable(); // Devoirs

            // Statistiques de présence
            $table->integer('total_students')->default(0); // Total élèves inscrits
            $table->integer('present_count')->default(0); // Nombre présents
            $table->integer('absent_count')->default(0); // Nombre absents
            $table->integer('late_count')->default(0); // Nombre en retard
            $table->integer('excused_count')->default(0); // Nombre excusés

            // Validation et notes
            $table->boolean('attendance_validated')->default(false);
            $table->text('teacher_notes')->nullable(); // Notes du professeur
            $table->text('admin_notes')->nullable(); // Notes administratives

            $table->timestamps();

            // Indexes pour performance
            $table->index(['session_date', 'start_time']);
            $table->index(['teacher_id', 'session_date']);
            $table->index(['class_id', 'subject_id', 'session_date']);
            $table->index(['academic_year_id', 'status']);
            $table->index('type');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('class_sessions');
    }
};
