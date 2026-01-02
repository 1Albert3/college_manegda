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
    { if (!Schema::hasTable('subjects')) Schema::create('subjects', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique(); // Ex: "MATH", "FR", "ANG"
            $table->string('name'); // Ex: "Mathématiques", "Français"
            $table->text('description')->nullable();
            $table->integer('coefficient')->default(1); // Coefficient pour le calcul de moyenne
            $table->string('color')->nullable(); // Couleur pour l'affichage (hex)
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();

            // Indexes
            $table->index('code');
            $table->index('is_active');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('subjects');
    }
};
