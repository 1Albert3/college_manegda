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
        if (!Schema::hasTable('scholarships')) Schema::create('scholarships', function (Blueprint $table) {
            $table->id();
            $table->foreignUuid('student_id')->constrained('students')->onDelete('cascade');
            $table->foreignUuid('academic_year_id')->constrained('academic_years')->onDelete('cascade');
            $table->string('name'); // Ex: "Bourse d'excellence", "Réduction famille nombreuse"
            $table->enum('type', ['bourse', 'reduction', 'exoneration', 'aide_sociale'])->default('bourse');
            $table->decimal('percentage', 5, 2)->nullable(); // Pourcentage de réduction (0-100)
            $table->decimal('fixed_amount', 10, 2)->nullable(); // Montant fixe de réduction
            $table->text('reason')->nullable(); // Raison de l'attribution
            $table->text('conditions')->nullable(); // Conditions de maintien
            $table->date('start_date'); // Date de début
            $table->date('end_date'); // Date de fin
            $table->enum('status', ['en_attente', 'active', 'suspendue', 'expiree', 'annulee'])->default('en_attente');
            $table->foreignUuid('approved_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamp('approved_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();

            // Indexes
            $table->index('student_id');
            $table->index('status');
            $table->index('type');
            $table->index(['student_id', 'academic_year_id']);
            $table->index(['start_date', 'end_date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('scholarships');
    }
};
