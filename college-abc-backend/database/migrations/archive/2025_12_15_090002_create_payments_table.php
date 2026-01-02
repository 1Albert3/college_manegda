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
        if (!Schema::hasTable('payments')) Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->string('receipt_number')->unique(); // Numéro de reçu unique
            $table->foreignUuid('student_id')->constrained('students')->onDelete('cascade');
            $table->foreignId('fee_type_id')->constrained('fee_types')->onDelete('restrict');
            $table->foreignUuid('academic_year_id')->constrained('academic_years')->onDelete('cascade');
            $table->decimal('amount', 10, 2); // Montant payé
            $table->date('payment_date'); // Date du paiement
            $table->enum('payment_method', ['especes', 'cheque', 'virement', 'mobile_money', 'carte'])->default('especes');
            $table->string('reference')->nullable(); // Référence transaction (chèque, virement, etc.)
            $table->string('payer_name')->nullable(); // Nom du payeur si différent du parent
            $table->text('notes')->nullable(); // Notes additionnelles
            $table->enum('status', ['en_attente', 'valide', 'annule'])->default('valide');
            $table->foreignUuid('validated_by')->nullable()->constrained('users')->onDelete('set null'); // Utilisateur qui a validé
            $table->timestamp('validated_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            // Indexes
            $table->index('receipt_number');
            $table->index('student_id');
            $table->index('payment_date');
            $table->index('status');
            $table->index(['student_id', 'academic_year_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
