<?php

/**
 * Migration: Tables élèves et tuteurs Maternelle/Primaire
 * Base de données: school_maternelle_primaire
 * 
 * Tables: students_mp, guardians_mp
 * Conforme au formulaire d'inscription du cahier des charges
 */

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $connection = 'school_mp';

    public function up(): void
    {
        // Table des élèves Maternelle/Primaire
        if (!Schema::connection($this->connection)->hasTable('students_mp')) Schema::connection($this->connection)->create('students_mp', function (Blueprint $table) {
            // Identifiant
            $table->uuid('id')->primary();
            $table->uuid('user_id')->nullable()->index(); // Lien compte utilisateur (core)

            // Matricule auto-généré: MP-2025-0001
            $table->string('matricule', 20)->unique();

            // INFORMATIONS ÉLÈVE (Section 1 du formulaire)
            $table->string('nom', 100)->index(); // Obligatoire
            $table->string('prenoms', 150)->index(); // Obligatoire
            $table->date('date_naissance')->index(); // Obligatoire
            $table->string('lieu_naissance', 100); // Obligatoire
            $table->enum('sexe', ['M', 'F'])->index(); // Obligatoire
            $table->string('nationalite', 50)->default('Burkinabè'); // Default: Burkinabè

            // Documents
            $table->string('photo_identite', 255)->nullable(); // Upload photo
            $table->string('extrait_naissance', 255)->nullable(); // Upload extrait

            // Statut inscription
            $table->enum('statut_inscription', ['nouveau', 'ancien', 'transfert'])->default('nouveau')->index();
            $table->string('etablissement_origine', 200)->nullable(); // Si transfert

            // Informations médicales (optionnelles)
            $table->enum('groupe_sanguin', ['A+', 'A-', 'B+', 'B-', 'AB+', 'AB-', 'O+', 'O-'])->nullable();
            $table->text('allergies')->nullable();
            $table->json('vaccinations')->nullable(); // Historique vaccins

            // Statut
            $table->boolean('is_active')->default(true)->index();

            // Timestamps
            $table->timestamps();
            $table->softDeletes()->index();

            // Index pour recherche
            $table->index(['nom', 'prenoms']);
            $table->fullText(['nom', 'prenoms', 'matricule']);
        });

        // Table des tuteurs/parents Maternelle/Primaire
        if (!Schema::connection($this->connection)->hasTable('guardians_mp')) Schema::connection($this->connection)->create('guardians_mp', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('student_id')->index(); // Référence élève

            // Type de tuteur
            $table->enum('type', ['pere', 'mere', 'tuteur'])->index();

            // INFORMATIONS PARENTS/TUTEURS (Section 3 du formulaire)
            $table->string('nom_complet', 200); // Obligatoire
            $table->string('profession', 100)->nullable(); // Optionnel
            $table->string('telephone_1', 20)->index(); // Obligatoire
            $table->string('telephone_2', 20)->nullable(); // Optionnel
            $table->string('email', 191)->nullable()->index(); // Optionnel
            $table->text('adresse_physique'); // Obligatoire

            // Contact d'urgence
            $table->boolean('est_contact_urgence')->default(false);

            // Si tuteur légal
            $table->string('lien_parente', 50)->nullable();

            // Lien compte utilisateur parent (core)
            $table->uuid('user_id')->nullable()->index();

            // Timestamps
            $table->timestamps();

            // Foreign key vers students_mp
            $table->foreign('student_id')
                ->references('id')
                ->on('students_mp')
                ->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::connection($this->connection)->dropIfExists('guardians_mp');
        Schema::connection($this->connection)->dropIfExists('students_mp');
    }
};
