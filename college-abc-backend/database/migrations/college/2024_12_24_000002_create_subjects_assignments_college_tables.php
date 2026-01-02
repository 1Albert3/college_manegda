<?php

/**
 * Migration: Tables matières et affectations enseignants Collège
 * Base de données: school_college
 */

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $connection = 'school_college';

    public function up(): void
    {
        // Table des matières Collège avec coefficients par niveau
        if (!Schema::connection($this->connection)->hasTable('subjects_college')) Schema::connection($this->connection)->create('subjects_college', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('code', 20)->unique();
            $table->string('nom', 100);

            // Coefficients par niveau (selon directives MENA)
            $table->unsignedTinyInteger('coefficient_6eme')->default(1);
            $table->unsignedTinyInteger('coefficient_5eme')->default(1);
            $table->unsignedTinyInteger('coefficient_4eme')->default(1);
            $table->unsignedTinyInteger('coefficient_3eme')->default(1);

            $table->decimal('volume_horaire_hebdo', 4, 2)->nullable();
            $table->boolean('is_obligatoire')->default(true);
            $table->boolean('is_active')->default(true)->index();

            $table->timestamps();
        });

        // Table des affectations enseignant-matière-classe
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
                ->on('teachers_college')
                ->onDelete('cascade');

            $table->foreign('subject_id')
                ->references('id')
                ->on('subjects_college')
                ->onDelete('cascade');

            $table->foreign('class_id')
                ->references('id')
                ->on('classes_college')
                ->onDelete('cascade');

            // Un enseignant enseigne une matière dans une classe une seule fois par année
            $table->unique(['teacher_id', 'subject_id', 'class_id', 'school_year_id'], 'unique_assignment');
        });

        // Inscriptions collège
        if (!Schema::connection($this->connection)->hasTable('enrollments_college')) Schema::connection($this->connection)->create('enrollments_college', function (Blueprint $table) {
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
                ->on('students_college')
                ->onDelete('cascade');

            $table->foreign('class_id')
                ->references('id')
                ->on('classes_college')
                ->onDelete('cascade');

            $table->unique(['student_id', 'school_year_id']);
        });
    }

    public function down(): void
    {
        Schema::connection($this->connection)->dropIfExists('enrollments_college');
        Schema::connection($this->connection)->dropIfExists('teacher_subject_assignments');
        Schema::connection($this->connection)->dropIfExists('subjects_college');
    }
};
