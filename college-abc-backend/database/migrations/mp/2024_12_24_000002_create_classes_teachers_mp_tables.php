<?php

/**
 * Migration: Tables des classes et enseignants Maternelle/Primaire
 * Base de données: school_maternelle_primaire
 * 
 * Tables: classes_mp, teachers_mp
 */

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $connection = 'school_mp';

    public function up(): void
    {
        // Table des enseignants Maternelle/Primaire
        if (!Schema::connection($this->connection)->hasTable('teachers_mp')) Schema::connection($this->connection)->create('teachers_mp', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('user_id')->unique(); // Compte utilisateur (core)

            // Matricule auto-généré
            $table->string('matricule', 20)->unique();

            // Qualifications
            $table->json('diplomes')->nullable(); // Liste diplômes avec uploads
            $table->json('specialites')->nullable(); // Spécialités
            $table->unsignedSmallInteger('anciennete_annees')->default(0);

            // Emploi
            $table->date('date_embauche');
            $table->enum('type_contrat', ['permanent', 'contractuel'])->index();
            $table->enum('statut', ['actif', 'conge', 'suspendu'])->default('actif')->index();

            // Observations
            $table->text('observations')->nullable();

            // Timestamps
            $table->timestamps();
            $table->softDeletes()->index();
        });

        // Table des classes Maternelle/Primaire
        if (!Schema::connection($this->connection)->hasTable('classes_mp')) Schema::connection($this->connection)->create('classes_mp', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('school_year_id')->index(); // Référence core.school_years

            // Niveau (PS=Petite Section, MS=Moyenne Section, GS=Grande Section)
            $table->enum('niveau', ['PS', 'MS', 'GS', 'CP1', 'CP2', 'CE1', 'CE2', 'CM1', 'CM2'])->index();

            // Désignation
            $table->string('nom', 20); // Ex: CE2 A

            // Seuils
            $table->unsignedSmallInteger('seuil_minimum')->default(15);
            $table->unsignedSmallInteger('seuil_maximum')->default(40);
            $table->unsignedSmallInteger('effectif_actuel')->default(0);

            // Attribution
            $table->string('salle', 50)->nullable();
            $table->uuid('teacher_id')->nullable()->index(); // Enseignant titulaire

            // Statut
            $table->boolean('is_active')->default(true)->index();

            // Timestamps
            $table->timestamps();
            $table->softDeletes()->index();

            // Contrainte unique: une seule classe de ce nom par année
            $table->unique(['school_year_id', 'nom']);

            // Foreign key vers teachers_mp
            $table->foreign('teacher_id')
                ->references('id')
                ->on('teachers_mp')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::connection($this->connection)->dropIfExists('classes_mp');
        Schema::connection($this->connection)->dropIfExists('teachers_mp');
    }
};
