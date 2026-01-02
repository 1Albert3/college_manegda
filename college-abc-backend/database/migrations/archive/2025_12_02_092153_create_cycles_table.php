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
    { if (!Schema::hasTable('cycles')) Schema::create('cycles', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // Ex: "Primaire", "Collège", "Lycée"
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->integer('order')->default(0); // Ordre d'affichage
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();

            // Indexes
            $table->index('slug');
            $table->index('order');
            $table->index('is_active');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cycles');
    }
};
