<?php

/**
 * Migration: Tables des matières et notes Maternelle/Primaire
 * Base de données: school_maternelle_primaire
 * 
 * Tables: subjects_mp, grades_mp, competences_mp
 * Programme officiel burkinabè
 */

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $connection = 'school_mp';

    public function up(): void
    {
        // Table des matières (Programme burkinabè)
        if (!Schema::connection($this->connection)->hasTable('subjects_mp')) Schema::connection($this->connection)->create('subjects_mp', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('code', 20)->unique();
            $table->string('nom', 100);
            $table->text('description')->nullable();

            // Catégorie (sciences, communication, eveil, etc.)
            $table->string('categorie', 50)->index();

            // Coefficients par niveau (0 si non applicable)
            $table->unsignedTinyInteger('coefficient_maternelle')->default(0);
            $table->unsignedTinyInteger('coefficient_cp_ce1')->default(0);
            $table->unsignedTinyInteger('coefficient_ce2')->default(0);
            $table->unsignedTinyInteger('coefficient_cm1_cm2')->default(0);

            // Colonne legacy/fallback (optionnel, pour compatibilité)
            $table->unsignedTinyInteger('coefficient')->default(1);

            // Type d'évaluation
            $table->enum('type_evaluation', ['competences', 'notes', 'mixte'])->default('notes');

            // Attributs
            $table->boolean('is_active')->default(true);
            $table->boolean('is_obligatoire')->default(true);
            $table->decimal('volume_horaire_hebdo', 4, 2)->nullable();

            $table->timestamps();
            $table->softDeletes(); // Ajout softDeletes pour cohérence
        });

        // Table des notes (Primaire)
        if (!Schema::connection($this->connection)->hasTable('grades_mp')) Schema::connection($this->connection)->create('grades_mp', function (Blueprint $table) {
            $table->uuid('id')->primary();

            // Références
            $table->uuid('student_id')->index();
            $table->uuid('subject_id')->index();
            $table->uuid('class_id')->index();
            $table->uuid('school_year_id')->index();

            // Période
            $table->enum('trimestre', ['1', '2', '3'])->index();

            // Type d'évaluation selon cahier des charges:
            // IO = Interrogation Orale (/10)
            // DV = Devoir (/20)
            // CP = Composition (/100)
            // TP = Travaux Pratiques (/20)
            $table->enum('type_evaluation', ['IO', 'DV', 'CP', 'TP'])->index();

            // Notes
            $table->decimal('note_sur', 5, 2); // Maximum: 10, 20 ou 100
            $table->decimal('note_obtenue', 5, 2);
            $table->decimal('note_sur_20', 5, 2); // Convertie sur 20

            // Détails
            $table->date('date_evaluation');
            $table->text('commentaire')->nullable();

            // Publication (verrouillage)
            $table->boolean('is_published')->default(false)->index();
            $table->timestamp('published_at')->nullable();

            // Traçabilité
            $table->uuid('recorded_by')->index(); // core.users (enseignant)

            $table->timestamps();

            // Foreign keys
            $table->foreign('student_id')->references('id')->on('students_mp')->onDelete('cascade');
            $table->foreign('subject_id')->references('id')->on('subjects_mp')->onDelete('cascade');
            $table->foreign('class_id')->references('id')->on('classes_mp')->onDelete('cascade');

            // Index composite pour requêtes fréquentes
            $table->index(['student_id', 'school_year_id', 'trimestre']);
        });

        // Table des compétences (Maternelle)
        if (!Schema::connection($this->connection)->hasTable('competences_mp')) Schema::connection($this->connection)->create('competences_mp', function (Blueprint $table) {
            $table->uuid('id')->primary();

            // Références
            $table->uuid('student_id')->index();
            $table->uuid('school_year_id')->index();

            // Domaine selon cahier des charges
            $table->enum('domaine', [
                'langage_oral',
                'activites_physiques',
                'activites_artistiques',
                'construction_nombre',
                'explorer_monde'
            ])->index();

            // Compétence évaluée
            $table->string('competence', 200);

            // Niveau d'acquisition
            $table->enum('niveau', ['acquis', 'en_cours', 'non_acquis']);

            // Période
            $table->enum('trimestre', ['1', '2', '3'])->index();

            // Observations
            $table->text('observations')->nullable();

            // Traçabilité
            $table->uuid('evaluated_by')->index(); // core.users

            $table->timestamps();

            // Foreign key
            $table->foreign('student_id')->references('id')->on('students_mp')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::connection($this->connection)->dropIfExists('competences_mp');
        Schema::connection($this->connection)->dropIfExists('grades_mp');
        Schema::connection($this->connection)->dropIfExists('subjects_mp');
    }
};
