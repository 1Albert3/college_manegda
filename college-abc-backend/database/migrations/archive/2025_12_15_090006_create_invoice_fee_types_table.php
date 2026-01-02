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
    { if (!Schema::hasTable('invoice_fee_types')) Schema::create('invoice_fee_types', function (Blueprint $table) {
            $table->id();
            $table->foreignId('invoice_id')->constrained('invoices')->onDelete('cascade');
            $table->foreignId('fee_type_id')->constrained('fee_types')->onDelete('restrict');
            $table->decimal('base_amount', 10, 2); // Montant de base du frais
            $table->decimal('discount_amount', 10, 2)->default(0); // Réduction sur ce frais spécifique
            $table->decimal('final_amount', 10, 2); // Montant final (base - discount)
            $table->integer('quantity')->default(1); // Pour les frais récurrents (mois, etc.)
            $table->timestamps();

            // Indexes
            $table->index('invoice_id');
            $table->index('fee_type_id');
            $table->unique(['invoice_id', 'fee_type_id']); // Une facture ne peut avoir qu'une ligne par type de frais
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('invoice_fee_types');
    }
};
