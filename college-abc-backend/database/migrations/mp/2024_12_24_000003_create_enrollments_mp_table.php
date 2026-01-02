<?php

/**
 * Migration: Table des inscriptions Maternelle/Primaire
 * Base de données: school_maternelle_primaire
 * 
 * Table: enrollments_mp
 * Workflow: Secrétariat → Direction → Comptabilité
 */

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $connection = 'school_mp';

    public function up(): void
    {
        // Table des inscriptions
        if (!Schema::connection($this->connection)->hasTable('enrollments_mp')) Schema::connection($this->connection)->create('enrollments_mp', function (Blueprint $table) {
            $table->uuid('id')->primary();

            // Références
            $table->uuid('student_id')->index();
            $table->uuid('class_id')->index();
            $table->uuid('school_year_id')->index(); // core.school_years

            // AFFECTATION SCOLAIRE (Section 2 du formulaire)
            $table->enum('regime', ['interne', 'demi_pensionnaire', 'externe'])->default('externe');

            // Workflow inscription
            $table->date('date_inscription');
            $table->enum('statut', ['en_attente', 'validee', 'refusee'])->default('en_attente')->index();
            $table->uuid('validated_by')->nullable(); // core.users
            $table->timestamp('validated_at')->nullable();
            $table->text('motif_refus')->nullable();

            // INFORMATIONS FINANCIÈRES (Section 4 du formulaire)
            $table->decimal('frais_scolarite', 12, 2); // Montant selon niveau
            $table->decimal('frais_cantine', 12, 2)->nullable();
            $table->decimal('frais_activites', 12, 2)->nullable();
            $table->decimal('frais_inscription', 12, 2)->default(10000); // 10,000 FCFA
            $table->decimal('total_a_payer', 12, 2); // Calculé

            // Mode de paiement
            $table->enum('mode_paiement', ['comptant', 'tranches_3'])->default('tranches_3');

            // Bourses et réductions
            $table->boolean('a_bourse')->default(false);
            $table->decimal('montant_bourse', 12, 2)->nullable();
            $table->decimal('pourcentage_bourse', 5, 2)->nullable(); // 0-100
            $table->string('type_bourse', 100)->nullable();

            // Montant final après réduction
            $table->decimal('montant_final', 12, 2);

            // Timestamps
            $table->timestamps();

            // Foreign keys
            $table->foreign('student_id')
                ->references('id')
                ->on('students_mp')
                ->onDelete('cascade');

            $table->foreign('class_id')
                ->references('id')
                ->on('classes_mp')
                ->onDelete('cascade');

            // Contrainte: un élève ne peut être inscrit qu'une fois par année
            $table->unique(['student_id', 'school_year_id']);
        });
    }

    public function down(): void
    {
        Schema::connection($this->connection)->dropIfExists('enrollments_mp');
    }
};
