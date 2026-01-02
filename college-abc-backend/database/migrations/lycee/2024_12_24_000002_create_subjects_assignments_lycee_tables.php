<?php

/**
 * Migration: Tables matières et affectations Lycée
 * Base de données: school_lycee
 * 
 * Coefficients différenciés par série
 */

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $connection = 'school_lycee';

    public function up(): void
    {
        // Matières Lycée avec coefficients par niveau et série
        if (!Schema::connection($this->connection)->hasTable('subjects_lycee')) Schema::connection($this->connection)->create('subjects_lycee', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('code', 20)->unique();
            $table->string('nom', 100);

            // Coefficient pour 2nde (toutes séries)
            $table->unsignedTinyInteger('coefficient_2nde')->nullable();

            // Coefficients par série pour 1ère
            $table->unsignedTinyInteger('coefficient_1ere_A')->nullable();
            $table->unsignedTinyInteger('coefficient_1ere_C')->nullable();
            $table->unsignedTinyInteger('coefficient_1ere_D')->nullable();
            $table->unsignedTinyInteger('coefficient_1ere_E')->nullable();
            $table->unsignedTinyInteger('coefficient_1ere_F')->nullable();
            $table->unsignedTinyInteger('coefficient_1ere_G')->nullable();

            // Coefficients par série pour Terminale
            $table->unsignedTinyInteger('coefficient_tle_A')->nullable();
            $table->unsignedTinyInteger('coefficient_tle_C')->nullable();
            $table->unsignedTinyInteger('coefficient_tle_D')->nullable();
            $table->unsignedTinyInteger('coefficient_tle_E')->nullable();
            $table->unsignedTinyInteger('coefficient_tle_F')->nullable();
            $table->unsignedTinyInteger('coefficient_tle_G')->nullable();

            $table->decimal('volume_horaire_hebdo', 4, 2)->nullable();
            $table->boolean('is_obligatoire')->default(true);
            $table->json('series_applicables')->nullable(); // Séries où cette matière est enseignée
            $table->boolean('is_active')->default(true)->index();

            $table->timestamps();
        });

        // Affectations enseignant-matière-classe
        if (!Schema::connection($this->connection)->hasTable('teacher_subject_assignments')) Schema::connection($this->connection)->create('teacher_subject_assignments', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('teacher_id')->index();
            $table->uuid('subject_id')->index();
            $table->uuid('class_id')->index();
            $table->uuid('school_year_id')->index();

            $table->decimal('heures_par_semaine', 4, 2);

            $table->timestamps();

            $table->foreign('teacher_id')
                ->references('id')
                ->on('teachers_lycee')
                ->onDelete('cascade');

            $table->foreign('subject_id')
                ->references('id')
                ->on('subjects_lycee')
                ->onDelete('cascade');

            $table->foreign('class_id')
                ->references('id')
                ->on('classes_lycee')
                ->onDelete('cascade');

            $table->unique(['teacher_id', 'subject_id', 'class_id', 'school_year_id'], 'unique_assignment_lycee');
        });

        // Inscriptions lycée
        if (!Schema::connection($this->connection)->hasTable('enrollments_lycee')) Schema::connection($this->connection)->create('enrollments_lycee', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('student_id')->index();
            $table->uuid('class_id')->index();
            $table->uuid('school_year_id')->index();

            $table->enum('regime', ['interne', 'demi_pensionnaire', 'externe'])->default('externe');
            $table->date('date_inscription');
            $table->enum('statut', ['en_attente', 'validee', 'refusee'])->default('en_attente')->index();
            $table->uuid('validated_by')->nullable();
            $table->timestamp('validated_at')->nullable();
            $table->text('motif_refus')->nullable();

            // Financier
            $table->decimal('frais_scolarite', 12, 2);
            $table->decimal('frais_cantine', 12, 2)->nullable();
            $table->decimal('frais_activites', 12, 2)->nullable();
            $table->decimal('frais_inscription', 12, 2)->default(10000);
            $table->decimal('total_a_payer', 12, 2);
            $table->enum('mode_paiement', ['comptant', 'tranches_3'])->default('tranches_3');
            $table->boolean('a_bourse')->default(false);
            $table->decimal('montant_bourse', 12, 2)->nullable();
            $table->decimal('pourcentage_bourse', 5, 2)->nullable();
            $table->string('type_bourse', 100)->nullable();
            $table->decimal('montant_final', 12, 2);

            $table->timestamps();

            $table->foreign('student_id')
                ->references('id')
                ->on('students_lycee')
                ->onDelete('cascade');

            $table->foreign('class_id')
                ->references('id')
                ->on('classes_lycee')
                ->onDelete('cascade');

            $table->unique(['student_id', 'school_year_id']);
        });
    }

    public function down(): void
    {
        Schema::connection($this->connection)->dropIfExists('enrollments_lycee');
        Schema::connection($this->connection)->dropIfExists('teacher_subject_assignments');
        Schema::connection($this->connection)->dropIfExists('subjects_lycee');
    }
};
