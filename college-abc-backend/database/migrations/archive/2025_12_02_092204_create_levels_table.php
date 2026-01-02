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
    { if (!Schema::hasTable('levels')) Schema::create('levels', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cycle_id')->constrained()->onDelete('cascade');
            $table->string('name'); // Ex: "6ème", "5ème", "2nde"
            $table->string('code')->unique(); // Ex: "6EME", "5EME", "2NDE"
            $table->text('description')->nullable();
            $table->integer('order')->default(0); // Ordre dans le cycle
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();

            // Indexes
            $table->index('cycle_id');
            $table->index('code');
            $table->index(['cycle_id', 'order']);
            $table->index('is_active');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('levels');
    }
};
