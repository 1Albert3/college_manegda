<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (!Schema::hasTable('invoices')) Schema::create('invoices', function (Blueprint $table) {
            $table->id();
            $table->string('invoice_number')->unique(); // Numéro de facture unique
            $table->foreignUuid('student_id')->constrained('students')->onDelete('cascade');
            $table->foreignUuid('academic_year_id')->constrained('academic_years')->onDelete('cascade');
            $table->enum('period', ['annuel', 'trimestriel_1', 'trimestriel_2', 'trimestriel_3', 'mensuel'])->default('annuel');
            $table->decimal('total_amount', 10, 2); // Montant total à payer
            $table->decimal('discount_amount', 10, 2)->default(0); // Réductions appliquées
            $table->decimal('paid_amount', 10, 2)->default(0); // Montant déjà payé
            $table->decimal('due_amount', 10, 2); // Reste à payer (calculé: total - discount - paid)
            $table->date('due_date'); // Date limite de paiement
            $table->date('issue_date'); // Date d'émission
            $table->enum('status', ['brouillon', 'emise', 'partiellement_payee', 'payee', 'en_retard', 'annulee'])->default('brouillon');
            $table->text('notes')->nullable();
            $table->foreignUuid('generated_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamp('generated_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            // Indexes
            $table->index('invoice_number');
            $table->index('student_id');
            $table->index('status');
            $table->index('due_date');
            $table->index(['student_id', 'academic_year_id', 'period']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('invoices');
    }
};
