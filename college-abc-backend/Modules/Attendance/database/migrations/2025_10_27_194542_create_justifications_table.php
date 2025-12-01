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
        Schema::create('justifications', function (Blueprint $table) {
            $table->uuid('id')->primary();

            // Relation avec l'absence
            $table->foreignUuid('attendance_id')->constrained('attendances')->onDelete('cascade');

            // Informations sur la justification
            $table->enum('type', [
                'medical_certificate',
                'parental_note',
                'administrative',
                'other'
            ])->default('parental_note');

            $table->text('reason')->nullable(); // Motif détaillé
            $table->text('description')->nullable(); // Description complète

            // Documents justificatifs
            $table->json('documents')->nullable(); // URLs des documents uploadés
            $table->string('medical_certificate_path')->nullable();

            // Statut de la justification
            $table->enum('status', [
                'pending',
                'approved',
                'rejected',
                'under_review'
            ])->default('pending');

            // Soumis par
            $table->foreignUuid('submitted_by')->constrained('users')->onDelete('cascade');
            $table->timestamp('submitted_at')->useCurrent();

            // Approuvé par (si applicable)
            $table->foreignUuid('approved_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamp('approved_at')->nullable();
            $table->text('approval_notes')->nullable();

            // Métadonnées
            $table->json('metadata')->nullable();
            $table->text('admin_notes')->nullable();

            $table->timestamps();

            // Contraintes uniques et indexes
            $table->unique('attendance_id'); // Une justification par absence
            $table->index(['status', 'submitted_at']);
            $table->index('approved_at');
        });
        //add $table->foreign('justification_id')->references('id')->on('justifications')->onDelete('set null'); in attendances table
        Schema::table('attendances', function (Blueprint $table) {
            $table->foreign('justification_id')->references('id')->on('justifications')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('justifications');
        Schema::table('attendances', function (Blueprint $table) {
            $table->dropForeign(['justification_id']);
        });
    }
};
