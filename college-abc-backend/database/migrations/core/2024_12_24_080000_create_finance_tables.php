<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migration: Finance Tables
 * 
 * Tables pour la gestion financière:
 * - invoices (factures)
 * - payments (paiements)
 * - fee_structures (grilles tarifaires)
 */
return new class extends Migration
{
    protected $connection = 'school_core';

    public function up(): void
    {
        // Table des factures
        if (!Schema::connection($this->connection)->hasTable('invoices')) Schema::connection($this->connection)->create('invoices', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('number', 20)->unique();

            // Élève (peut venir de différentes bases)
            $table->uuid('student_id');
            $table->enum('student_database', ['school_mp', 'school_college', 'school_lycee']);

            // Références
            $table->uuid('enrollment_id')->nullable();
            $table->uuid('school_year_id');

            // Type et description
            $table->enum('type', ['inscription', 'scolarite', 'cantine', 'transport', 'fournitures', 'autre']);
            $table->string('description')->nullable();

            // Montants
            $table->decimal('montant_ht', 12, 2)->default(0);
            $table->decimal('montant_ttc', 12, 2);
            $table->decimal('montant_paye', 12, 2)->default(0);
            $table->decimal('solde', 12, 2);

            // Statut et dates
            $table->enum('statut', ['brouillon', 'emise', 'partiellement_payee', 'payee', 'annulee'])->default('brouillon');
            $table->date('date_emission');
            $table->date('date_echeance')->nullable();

            // Métadonnées
            $table->text('notes')->nullable();
            $table->uuid('created_by');

            $table->timestamps();
            $table->softDeletes();

            $table->index(['student_id', 'student_database']);
            $table->index('school_year_id');
            $table->index('statut');
            $table->index('date_echeance');
        });

        // Table des paiements
        if (!Schema::connection($this->connection)->hasTable('payments')) Schema::connection($this->connection)->create('payments', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('reference', 20)->unique();

            // Facture associée
            $table->uuid('invoice_id');

            // Élève (redondant mais utile pour les recherches)
            $table->uuid('student_id');
            $table->enum('student_database', ['school_mp', 'school_college', 'school_lycee']);

            // Montant et mode
            $table->decimal('montant', 12, 2);
            $table->enum('mode_paiement', ['especes', 'cheque', 'virement', 'mobile_money', 'carte']);
            $table->date('date_paiement');

            // Détails selon le mode
            $table->string('reference_transaction', 100)->nullable(); // Pour mobile money
            $table->string('banque', 100)->nullable(); // Pour chèque/virement
            $table->string('numero_cheque', 50)->nullable();

            // Statut et validation
            $table->enum('statut', ['en_attente', 'valide', 'rejete', 'annule'])->default('en_attente');
            $table->text('notes')->nullable();

            // Agents
            $table->uuid('received_by');
            $table->uuid('validated_by')->nullable();
            $table->timestamp('validated_at')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->foreign('invoice_id')->references('id')->on('invoices');
            $table->index(['student_id', 'student_database']);
            $table->index('date_paiement');
            $table->index('statut');
            $table->index('mode_paiement');
        });

        // Grilles tarifaires
        if (!Schema::connection($this->connection)->hasTable('fee_structures')) Schema::connection($this->connection)->create('fee_structures', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('school_year_id');

            // Niveau et cycle
            $table->enum('cycle', ['maternelle', 'primaire', 'college', 'lycee']);
            $table->string('niveau', 20);
            $table->string('serie', 10)->nullable(); // Pour le lycée

            // Frais de base
            $table->decimal('inscription', 10, 2)->default(0);
            $table->decimal('scolarite', 10, 2)->default(0);
            $table->decimal('apee', 10, 2)->default(0); // Association parents d'élèves
            $table->decimal('assurance', 10, 2)->default(0);
            $table->decimal('tenue', 10, 2)->default(0);
            $table->decimal('fournitures', 10, 2)->default(0);

            // Total
            $table->decimal('total', 10, 2);

            // Options
            $table->decimal('cantine_mensuel', 10, 2)->nullable();
            $table->decimal('transport_mensuel', 10, 2)->nullable();

            // Réductions
            $table->decimal('reduction_frere_soeur', 5, 2)->default(0); // Pourcentage
            $table->decimal('reduction_paiement_integral', 5, 2)->default(0);

            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['school_year_id', 'cycle', 'niveau', 'serie']);
        });

        // Échéanciers de paiement
        if (!Schema::connection($this->connection)->hasTable('payment_schedules')) Schema::connection($this->connection)->create('payment_schedules', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('enrollment_id');

            $table->integer('numero_echeance');
            $table->decimal('montant', 10, 2);
            $table->date('date_echeance');
            $table->boolean('is_paid')->default(false);
            $table->date('date_paiement')->nullable();

            $table->timestamps();

            $table->index('enrollment_id');
            $table->index('date_echeance');
        });

        // Remises et bourses
        if (!Schema::connection($this->connection)->hasTable('scholarships')) Schema::connection($this->connection)->create('scholarships', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('student_id');
            $table->enum('student_database', ['school_mp', 'school_college', 'school_lycee']);
            $table->uuid('school_year_id');

            $table->enum('type', ['bourse_merite', 'bourse_sociale', 'reduction_fratrie', 'autre']);
            $table->text('motif');
            $table->enum('mode', ['pourcentage', 'montant']); // Réduction en % ou montant fixe
            $table->decimal('valeur', 10, 2); // Valeur de la réduction
            $table->decimal('montant_accorde', 10, 2); // Montant final calculé

            $table->uuid('approved_by')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->enum('statut', ['en_attente', 'approuve', 'refuse'])->default('en_attente');

            $table->timestamps();

            $table->index(['student_id', 'student_database']);
            $table->index('school_year_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('scholarships');
        Schema::dropIfExists('payment_schedules');
        Schema::dropIfExists('fee_structures');
        Schema::dropIfExists('payments');
        Schema::dropIfExists('invoices');
    }
};
