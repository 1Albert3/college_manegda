<?php

/**
 * Migration: Tables absences, bulletins et historique Maternelle/Primaire
 * Base de données: school_maternelle_primaire
 * 
 * Tables: attendances_mp, report_cards_mp, student_history_mp
 */

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $connection = 'school_mp';

    public function up(): void
    {
        // Table des absences et retards
        if (!Schema::connection($this->connection)->hasTable('attendances_mp')) Schema::connection($this->connection)->create('attendances_mp', function (Blueprint $table) {
            $table->uuid('id')->primary();

            // Références
            $table->uuid('student_id')->index();
            $table->date('date')->index();

            // Type
            $table->enum('type', ['absence', 'retard'])->index();

            // Détails
            $table->json('creneaux')->nullable(); // Créneaux concernés
            $table->json('matieres_manquees')->nullable();

            // Statut justification
            $table->enum('statut', ['justifiee', 'non_justifiee', 'en_attente'])->default('en_attente')->index();
            $table->string('justificatif', 255)->nullable(); // Upload document
            $table->text('motif')->nullable();

            // Si retard
            $table->time('heure_arrivee')->nullable();
            $table->unsignedSmallInteger('duree_retard_minutes')->nullable();

            // Sanction
            $table->text('sanction')->nullable();

            // Traçabilité
            $table->uuid('recorded_by')->index(); // core.users
            $table->uuid('validated_by')->nullable();
            $table->timestamp('validated_at')->nullable();

            $table->timestamps();
            $table->softDeletes();

            // Foreign key
            $table->foreign('student_id')->references('id')->on('students_mp')->onDelete('cascade');

            // Index composite
            $table->index(['student_id', 'date', 'type']);
        });

        // Table des bulletins
        if (!Schema::connection($this->connection)->hasTable('report_cards_mp')) Schema::connection($this->connection)->create('report_cards_mp', function (Blueprint $table) {
            $table->uuid('id')->primary();

            // Références
            $table->uuid('student_id')->index();
            $table->uuid('class_id')->index();
            $table->uuid('school_year_id')->index();

            // Période
            $table->enum('trimestre', ['1', '2', '3'])->index();

            // Résultats calculés (arrondi 2 décimales)
            $table->decimal('moyenne_generale', 5, 2);
            $table->decimal('total_points', 8, 2);
            $table->unsignedSmallInteger('total_coefficients');

            // Classement
            $table->unsignedSmallInteger('rang')->nullable();
            $table->unsignedSmallInteger('effectif_classe');

            // Statistiques classe
            $table->decimal('moyenne_classe', 5, 2);
            $table->decimal('moyenne_premier', 5, 2);
            $table->decimal('moyenne_dernier', 5, 2);

            // Absences
            $table->unsignedSmallInteger('absences_justifiees')->default(0);
            $table->unsignedSmallInteger('absences_non_justifiees')->default(0);
            $table->unsignedSmallInteger('retards')->default(0);

            // Mention automatique selon cahier des charges
            $table->enum('mention', [
                'excellent',      // >= 18
                'tres_bien',      // 16-17.99
                'bien',           // 14-15.99
                'assez_bien',     // 12-13.99
                'passable',       // 10-11.99
                'insuffisant'     // < 10
            ])->nullable();

            // Appréciations
            $table->text('appreciation_generale')->nullable();

            // Décision
            $table->enum('decision', ['passage', 'redoublement', 'conditionnel'])->nullable();

            // Données détaillées par matière (JSON)
            $table->json('data_matieres');

            // Validation
            $table->boolean('is_validated')->default(false)->index();
            $table->uuid('validated_by')->nullable();
            $table->timestamp('validated_at')->nullable();

            // PDF généré
            $table->string('pdf_path', 255)->nullable();

            $table->timestamps();

            // Foreign keys
            $table->foreign('student_id')->references('id')->on('students_mp')->onDelete('cascade');
            $table->foreign('class_id')->references('id')->on('classes_mp')->onDelete('cascade');

            // Contrainte unique
            $table->unique(['student_id', 'school_year_id', 'trimestre']);
        });

        // Table historique élèves (suivi longitudinal)
        if (!Schema::connection($this->connection)->hasTable('student_history_mp')) Schema::connection($this->connection)->create('student_history_mp', function (Blueprint $table) {
            $table->uuid('id')->primary();

            // Références
            $table->uuid('student_id')->index();
            $table->uuid('school_year_id')->index();
            $table->uuid('class_id')->index();

            // Données année
            $table->string('niveau', 20);
            $table->decimal('moyenne_annuelle', 5, 2)->nullable();
            $table->enum('decision', ['passage', 'redoublement']);
            $table->unsignedSmallInteger('rang_annuel')->nullable();

            // Observations
            $table->text('observations')->nullable();

            // Migration inter-bases
            $table->enum('migrated_to', ['college'])->nullable();
            $table->uuid('migrated_student_id')->nullable();
            $table->timestamp('migrated_at')->nullable();

            // Archivage
            $table->boolean('is_archived')->default(false);

            $table->timestamps();

            // Foreign keys
            $table->foreign('student_id')->references('id')->on('students_mp')->onDelete('cascade');
            $table->foreign('class_id')->references('id')->on('classes_mp')->onDelete('cascade');

            // Unique par année
            $table->unique(['student_id', 'school_year_id']);
        });
    }

    public function down(): void
    {
        Schema::connection($this->connection)->dropIfExists('student_history_mp');
        Schema::connection($this->connection)->dropIfExists('report_cards_mp');
        Schema::connection($this->connection)->dropIfExists('attendances_mp');
    }
};
