<?php

/**
 * Migration: Tables notes, absences et bulletins Collège
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
        // Notes collège
        if (!Schema::connection($this->connection)->hasTable('grades_college')) Schema::connection($this->connection)->create('grades_college', function (Blueprint $table) {
            $table->uuid('id')->primary();

            $table->uuid('student_id')->index();
            $table->uuid('subject_id')->index();
            $table->uuid('class_id')->index();
            $table->uuid('school_year_id')->index();

            $table->enum('trimestre', ['1', '2', '3'])->index();

            // Types d'évaluation collège:
            // IE = Interrogation Écrite
            // DS = Devoir Surveillé  
            // Comp = Composition
            // TP = Travaux Pratiques
            // CC = Contrôle Continu
            $table->enum('type_evaluation', ['IE', 'DS', 'Comp', 'TP', 'CC'])->index();

            $table->decimal('note_sur', 5, 2)->default(20);
            $table->decimal('note_obtenue', 5, 2);
            $table->decimal('note_sur_20', 5, 2);
            $table->unsignedSmallInteger('coefficient')->default(1);

            $table->date('date_evaluation');
            $table->text('commentaire')->nullable();

            $table->boolean('is_published')->default(false)->index();
            $table->timestamp('published_at')->nullable();

            $table->uuid('recorded_by')->index();

            $table->timestamps();
            $table->softDeletes()->index();

            $table->foreign('student_id')->references('id')->on('students_college')->onDelete('cascade');
            $table->foreign('subject_id')->references('id')->on('subjects_college')->onDelete('cascade');
            $table->foreign('class_id')->references('id')->on('classes_college')->onDelete('cascade');

            $table->index(['student_id', 'school_year_id', 'trimestre']);
        });

        // Absences collège
        if (!Schema::connection($this->connection)->hasTable('attendance_college')) Schema::connection($this->connection)->create('attendance_college', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('student_id')->index();
            $table->date('date')->index();
            $table->enum('type', ['absence', 'retard'])->index();
            $table->json('creneaux')->nullable();
            $table->json('matieres_manquees')->nullable();
            $table->enum('statut', ['justifiee', 'non_justifiee', 'en_attente'])->default('en_attente')->index();
            $table->string('justificatif', 255)->nullable();
            $table->text('motif')->nullable();
            $table->time('heure_arrivee')->nullable();
            $table->unsignedSmallInteger('duree_retard_minutes')->nullable();
            $table->text('sanction')->nullable();
            $table->uuid('recorded_by')->index();
            $table->uuid('validated_by')->nullable();
            $table->timestamp('validated_at')->nullable();
            $table->timestamps();

            $table->foreign('student_id')->references('id')->on('students_college')->onDelete('cascade');
            $table->index(['student_id', 'date', 'type']);
        });

        // Bulletins collège
        if (!Schema::connection($this->connection)->hasTable('report_cards_college')) Schema::connection($this->connection)->create('report_cards_college', function (Blueprint $table) {
            $table->uuid('id')->primary();

            $table->uuid('student_id')->index();
            $table->uuid('class_id')->index();
            $table->uuid('school_year_id')->index();
            $table->enum('trimestre', ['1', '2', '3'])->index();

            $table->decimal('moyenne_generale', 5, 2);
            $table->decimal('total_points', 8, 2);
            $table->unsignedSmallInteger('total_coefficients');

            $table->unsignedSmallInteger('rang')->nullable();
            $table->unsignedSmallInteger('effectif_classe');
            $table->decimal('moyenne_classe', 5, 2);
            $table->decimal('moyenne_premier', 5, 2);
            $table->decimal('moyenne_dernier', 5, 2);

            $table->unsignedSmallInteger('absences_justifiees')->default(0);
            $table->unsignedSmallInteger('absences_non_justifiees')->default(0);
            $table->unsignedSmallInteger('retards')->default(0);

            $table->enum('mention', [
                'excellent',
                'tres_bien',
                'bien',
                'assez_bien',
                'passable',
                'insuffisant'
            ])->nullable();

            $table->text('appreciation_generale')->nullable();
            $table->json('appreciations_matieres')->nullable(); // Appréciation par matière
            $table->enum('decision', ['passage', 'redoublement', 'conditionnel'])->nullable();

            $table->json('data_matieres');

            $table->boolean('is_published')->default(false)->index();
            $table->uuid('published_by')->nullable();
            $table->timestamp('published_at')->nullable();
            $table->string('pdf_path', 255)->nullable();

            $table->timestamps();
            $table->softDeletes()->index();

            $table->foreign('student_id')->references('id')->on('students_college')->onDelete('cascade');
            $table->foreign('class_id')->references('id')->on('classes_college')->onDelete('cascade');

            $table->unique(['student_id', 'school_year_id', 'trimestre']);
        });

        // Discipline collège
        if (!Schema::connection($this->connection)->hasTable('discipline_college')) Schema::connection($this->connection)->create('discipline_college', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('student_id')->index();

            $table->enum('type', [
                'avertissement',
                'blame',
                'exclusion_temporaire',
                'conseil_discipline'
            ])->index();

            $table->date('date_incident')->index();
            $table->text('motif');
            $table->unsignedSmallInteger('duree_jours')->nullable(); // Si exclusion
            $table->text('decision')->nullable();
            $table->string('pv_path', 255)->nullable(); // Procès-verbal

            $table->uuid('recorded_by')->index();
            $table->boolean('is_notified_parent')->default(false);
            $table->timestamp('notified_at')->nullable();

            $table->timestamps();

            $table->foreign('student_id')->references('id')->on('students_college')->onDelete('cascade');
        });

        // Historique collège
        if (!Schema::connection($this->connection)->hasTable('student_history_college')) Schema::connection($this->connection)->create('student_history_college', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('student_id')->index();
            $table->uuid('school_year_id')->index();
            $table->uuid('class_id')->index();
            $table->string('niveau', 20);
            $table->decimal('moyenne_annuelle', 5, 2)->nullable();
            $table->enum('decision', ['passage', 'redoublement']);
            $table->unsignedSmallInteger('rang_annuel')->nullable();
            $table->text('observations')->nullable();
            $table->enum('migrated_to', ['lycee'])->nullable();
            $table->uuid('migrated_student_id')->nullable();
            $table->timestamp('migrated_at')->nullable();
            $table->boolean('is_archived')->default(false);
            $table->timestamps();

            $table->foreign('student_id')->references('id')->on('students_college')->onDelete('cascade');
            $table->foreign('class_id')->references('id')->on('classes_college')->onDelete('cascade');

            $table->unique(['student_id', 'school_year_id']);
        });
    }

    public function down(): void
    {
        Schema::connection($this->connection)->dropIfExists('student_history_college');
        Schema::connection($this->connection)->dropIfExists('discipline_college');
        Schema::connection($this->connection)->dropIfExists('report_cards_college');
        Schema::connection($this->connection)->dropIfExists('attendance_college');
        Schema::connection($this->connection)->dropIfExists('grades_college');
    }
};
