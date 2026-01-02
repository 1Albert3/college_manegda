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
    { if (!Schema::hasTable('fee_types')) Schema::create('fee_types', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // Ex: "Scolarité", "Inscription", "Cantine"
            $table->text('description')->nullable();
            $table->decimal('amount', 10, 2); // Montant en FCFA
            $table->enum('frequency', ['mensuel', 'trimestriel', 'annuel', 'unique'])->default('annuel');
            $table->foreignId('cycle_id')->nullable()->constrained('cycles')->onDelete('cascade'); // Optionnel si spécifique à un cycle
            $table->foreignId('level_id')->nullable()->constrained('levels')->onDelete('cascade'); // Optionnel si spécifique à un niveau
            $table->boolean('is_mandatory')->default(true); // Obligatoire ou facultatif
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();

            // Indexes
            $table->index('name');
            $table->index('frequency');
            $table->index('is_active');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('fee_types');
    }
};
