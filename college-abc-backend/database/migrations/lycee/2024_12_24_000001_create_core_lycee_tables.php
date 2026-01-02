<?php

/**
 * Migration: Tables principales Lycée
 * Base de données: school_lycee
 * 
 * Support des séries: A, C, D, E, F, G
 */

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $connection = 'school_lycee';

    public function up(): void
    {
        // Enseignants Lycée
        if (!Schema::connection($this->connection)->hasTable('teachers_lycee')) Schema::connection($this->connection)->create('teachers_lycee', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('user_id')->unique();
            $table->string('matricule', 20)->unique();
            $table->json('diplomes')->nullable();
            $table->json('specialites')->nullable();
            $table->unsignedSmallInteger('anciennete_annees')->default(0);
            $table->date('date_embauche');
            $table->enum('type_contrat', ['permanent', 'contractuel'])->index();
            $table->enum('statut', ['actif', 'conge', 'suspendu'])->default('actif')->index();
            $table->unsignedSmallInteger('heures_semaine_max')->default(18);
            $table->text('observations')->nullable();
            $table->timestamps();
            $table->softDeletes()->index();
        });

        // Élèves Lycée
        if (!Schema::connection($this->connection)->hasTable('students_lycee')) Schema::connection($this->connection)->create('students_lycee', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('user_id')->nullable()->index();
            $table->string('matricule', 20)->unique(); // LYC-2025-0001

            // Informations élève
            $table->string('nom', 100)->index();
            $table->string('prenoms', 150)->index();
            $table->date('date_naissance')->index();
            $table->string('lieu_naissance', 100);
            $table->enum('sexe', ['M', 'F'])->index();
            $table->string('nationalite', 50)->default('Burkinabè');

            // Documents
            $table->string('photo_identite', 255)->nullable();
            $table->string('extrait_naissance', 255)->nullable();

            // Statut
            $table->enum('statut_inscription', ['nouveau', 'ancien', 'transfert'])->default('nouveau')->index();
            $table->string('etablissement_origine', 200)->nullable();

            // Médical
            $table->enum('groupe_sanguin', ['A+', 'A-', 'B+', 'B-', 'AB+', 'AB-', 'O+', 'O-'])->nullable();
            $table->text('allergies')->nullable();
            $table->json('vaccinations')->nullable();

            // SPÉCIFIQUE LYCÉE
            $table->enum('serie', ['A', 'C', 'D', 'E', 'F', 'G'])->nullable()->index(); // Série (null pour 2nde)
            $table->string('specialite', 100)->nullable(); // Si applicable
            $table->json('orientation_post_bac')->nullable(); // Vœux orientation

            // Migration depuis collège
            $table->uuid('previous_school_id')->nullable();
            $table->boolean('migrated_from_college')->default(false);

            $table->boolean('is_active')->default(true)->index();
            $table->timestamps();
            $table->softDeletes()->index();

            $table->index(['nom', 'prenoms']);
        });

        // Classes Lycée avec séries
        if (!Schema::connection($this->connection)->hasTable('classes_lycee')) Schema::connection($this->connection)->create('classes_lycee', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('school_year_id')->index();

            $table->enum('niveau', ['2nde', '1ere', 'Tle'])->index();
            $table->enum('serie', ['A', 'C', 'D', 'E', 'F', 'G'])->nullable()->index(); // Null pour 2nde
            $table->string('nom', 20); // Ex: 1ère C, Tle D

            $table->unsignedSmallInteger('seuil_minimum')->default(15);
            $table->unsignedSmallInteger('seuil_maximum')->default(45);
            $table->unsignedSmallInteger('effectif_actuel')->default(0);

            $table->string('salle', 50)->nullable();
            $table->uuid('prof_principal_id')->nullable()->index();

            $table->boolean('is_active')->default(true)->index();
            $table->timestamps();
            $table->softDeletes()->index();

            $table->unique(['school_year_id', 'nom']);

            $table->foreign('prof_principal_id')
                ->references('id')
                ->on('teachers_lycee')
                ->nullOnDelete();
        });

        // Tuteurs Lycée
        if (!Schema::connection($this->connection)->hasTable('guardians_lycee')) Schema::connection($this->connection)->create('guardians_lycee', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('student_id')->index();
            $table->enum('type', ['pere', 'mere', 'tuteur'])->index();
            $table->string('nom_complet', 200);
            $table->string('profession', 100)->nullable();
            $table->string('telephone_1', 20)->index();
            $table->string('telephone_2', 20)->nullable();
            $table->string('email', 191)->nullable()->index();
            $table->text('adresse_physique');
            $table->boolean('est_contact_urgence')->default(false);
            $table->string('lien_parente', 50)->nullable();
            $table->uuid('user_id')->nullable()->index();
            $table->timestamps();

            $table->foreign('student_id')
                ->references('id')
                ->on('students_lycee')
                ->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::connection($this->connection)->dropIfExists('guardians_lycee');
        Schema::connection($this->connection)->dropIfExists('classes_lycee');
        Schema::connection($this->connection)->dropIfExists('students_lycee');
        Schema::connection($this->connection)->dropIfExists('teachers_lycee');
    }
};
