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
        Schema::create('attendances', function (Blueprint $table) {
            $table->uuid('id')->primary();


            // Relations principales
            $table->foreignUuid('student_id')->constrained('students')->onDelete('cascade');
            $table->foreignUuid('session_id')->constrained('class_sessions')->onDelete('cascade');

            // Statut de présence
            $table->enum('status', [
                'present',
                'absent',
                'late',
                'excused',
                'partially_present'
            ])->default('present');

            // Détails temporels
            $table->timestamp('check_in_time')->nullable(); // Heure d'arrivée réelle
            $table->timestamp('check_out_time')->nullable(); // Heure de départ réelle
            $table->integer('minutes_late')->default(0); // Minutes de retard

            // Suivi des justifications pour absences
            $table->boolean('justified')->default(false);
            $table->uuid('justification_id')->nullable();
            // $table->foreign('justification_id')->references('id')->on('justifications')->onDelete('set null');

            // Motif d'absence/non-présence
            $table->enum('absence_reason', [
                'illness',
                'family_emergency',
                'personal_reasons',
                'transport_issues',
                'other'
            ])->nullable();

            $table->text('absence_notes')->nullable(); // Explications détaillées

            // Approbation administrative
            $table->boolean('admin_approved')->default(false);
            $table->foreignUuid('approved_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamp('approved_at')->nullable();

            // Horodatage et enregistrement
            $table->timestamp('recorded_at')->useCurrent();
            $table->foreignUuid('recorded_by')->constrained('users')->onDelete('cascade'); // Qui a noté la présence

            // Métadonnées
            $table->json('metadata')->nullable(); // Données supplémentaires (GPS, appareil, etc.)
            $table->text('teacher_notes')->nullable(); // Remarques du professeur
            $table->text('admin_notes')->nullable(); // Remarques administratives

            $table->timestamps();

            // Contraintes uniques et indexes
            $table->unique(['student_id', 'session_id']); // Une seule présence par élève par séance
            $table->index(['session_id', 'status']);
            $table->index(['student_id', 'recorded_at']);
            $table->index(['status', 'justified']);
            $table->index('check_in_time');
            $table->index(['recorded_at', 'recorded_by']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('attendances');
    }
};
