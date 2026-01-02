<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migration: Tables Discipline
 * 
 * Gestion des sanctions et du conseil de discipline
 */
return new class extends Migration
{
    protected $connection = 'school_college';

    /**
     * Tables pour Collège
     */
    public function up(): void
    {
        // Table des incidents disciplinaires
        if (!Schema::connection($this->connection)->hasTable('discipline_incidents')) Schema::connection($this->connection)->create('discipline_incidents', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('student_id');
            $table->uuid('class_id');
            $table->uuid('school_year_id');

            // Détails de l'incident
            $table->date('date_incident');
            $table->time('heure_incident')->nullable();
            $table->string('lieu', 100); // Salle, Cour, Cantine, etc.

            // Type et gravité
            $table->enum('type', [
                'comportement',     // Mauvais comportement
                'violence',         // Violence physique ou verbale
                'retards_repetes', // Retards répétés
                'absences',        // Absences injustifiées
                'tricherie',       // Fraude aux examens
                'degradation',     // Dégradation matériel
                'insolence',       // Insolence envers personnel
                'tenue',           // Non-respect tenue vestimentaire
                'autre'
            ]);
            $table->enum('gravite', ['mineure', 'moyenne', 'grave', 'tres_grave']);

            // Description
            $table->text('description');
            $table->text('circonstances')->nullable();
            $table->json('temoins')->nullable(); // Liste des témoins

            // Signalement
            $table->uuid('signale_par'); // Enseignant ou personnel
            $table->enum('statut', ['signale', 'en_cours', 'traite', 'classe'])->default('signale');

            $table->timestamps();

            $table->foreign('student_id')->references('id')->on('students_college');
            $table->foreign('class_id')->references('id')->on('classes_college');
            $table->index(['student_id', 'school_year_id']);
            $table->index('date_incident');
        });

        // Table des sanctions
        if (!Schema::connection($this->connection)->hasTable('discipline_sanctions')) Schema::connection($this->connection)->create('discipline_sanctions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('incident_id');
            $table->uuid('student_id');
            $table->uuid('school_year_id');

            // Type de sanction
            $table->enum('type', [
                'avertissement_oral',
                'avertissement_ecrit',
                'blame',
                'retenue',
                'travail_interet_general',
                'exclusion_temporaire',
                'exclusion_cours',
                'conseil_discipline',
                'exclusion_definitive'
            ]);

            // Détails
            $table->text('motif');
            $table->date('date_effet');
            $table->date('date_fin')->nullable(); // Pour exclusions temporaires
            $table->integer('duree_jours')->nullable(); // Durée en jours

            // Notification
            $table->boolean('parents_notifies')->default(false);
            $table->timestamp('date_notification_parents')->nullable();
            $table->enum('mode_notification', ['sms', 'email', 'courrier', 'convocation'])->nullable();

            // Validation
            $table->uuid('decide_par'); // Qui a décidé
            $table->enum('niveau_decision', ['enseignant', 'censorat', 'direction', 'conseil']);
            $table->text('observations')->nullable();

            // Appel
            $table->boolean('appel_fait')->default(false);
            $table->text('resultat_appel')->nullable();

            $table->timestamps();

            $table->foreign('incident_id')->references('id')->on('discipline_incidents');
            $table->index(['student_id', 'school_year_id']);
            $table->index('type');
        });

        // Table des conseils de discipline
        if (!Schema::connection($this->connection)->hasTable('discipline_councils')) Schema::connection($this->connection)->create('discipline_councils', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('student_id');
            $table->uuid('incident_id')->nullable();
            $table->uuid('school_year_id');

            // Date et lieu
            $table->date('date_conseil');
            $table->time('heure_debut');
            $table->string('lieu', 100);

            // Motif
            $table->text('motif_convocation');
            $table->json('incidents_concernes')->nullable(); // Liste des incidents

            // Composition du conseil
            $table->json('membres_presents'); // [{id, nom, role}]
            $table->boolean('parent_present')->default(false);
            $table->boolean('eleve_present')->default(true);
            $table->string('representant_eleve')->nullable(); // Délégué de classe

            // Délibération
            $table->text('resume_debats')->nullable();
            $table->text('defense_eleve')->nullable();
            $table->text('interventions_parents')->nullable();

            // Décision
            $table->enum('decision', [
                'blame_conseil',
                'exclusion_temporaire',
                'exclusion_definitive',
                'sursis',
                'relaxe',
                'mesures_accompagnement'
            ])->nullable();
            $table->integer('duree_exclusion')->nullable(); // En jours
            $table->text('conditions_reintegration')->nullable();

            // Vote
            $table->integer('votes_pour')->nullable();
            $table->integer('votes_contre')->nullable();
            $table->integer('abstentions')->nullable();

            // Statut
            $table->enum('statut', ['programme', 'en_cours', 'termine', 'annule'])->default('programme');
            $table->timestamp('date_pv')->nullable();
            $table->string('pv_path')->nullable(); // Chemin du PV PDF

            $table->timestamps();

            $table->foreign('student_id')->references('id')->on('students_college');
            $table->index('date_conseil');
        });

        // Table récapitulative comportement
        if (!Schema::connection($this->connection)->hasTable('discipline_summaries')) Schema::connection($this->connection)->create('discipline_summaries', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('student_id');
            $table->uuid('school_year_id');
            $table->integer('trimestre');

            // Compteurs
            $table->integer('nb_avertissements')->default(0);
            $table->integer('nb_blames')->default(0);
            $table->integer('nb_retenues')->default(0);
            $table->integer('nb_exclusions')->default(0);
            $table->integer('jours_exclusion_total')->default(0);

            // Appréciation
            $table->enum('comportement_general', [
                'exemplaire',
                'tres_bon',
                'bon',
                'moyen',
                'insuffisant',
                'inacceptable'
            ])->nullable();
            $table->text('observations')->nullable();

            // Points positifs
            $table->integer('nb_felicitations')->default(0);
            $table->integer('nb_encouragements')->default(0);
            $table->integer('nb_tableaux_honneur')->default(0);

            $table->timestamps();

            $table->unique(['student_id', 'school_year_id', 'trimestre']);
        });
    }

    public function down(): void
    {
        Schema::connection($this->connection)->dropIfExists('discipline_summaries');
        Schema::connection($this->connection)->dropIfExists('discipline_councils');
        Schema::connection($this->connection)->dropIfExists('discipline_sanctions');
        Schema::connection($this->connection)->dropIfExists('discipline_incidents');
    }
};
