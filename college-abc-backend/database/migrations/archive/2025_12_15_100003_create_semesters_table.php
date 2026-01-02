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
    { if (!Schema::hasTable('semesters')) Schema::create('semesters', function (Blueprint $table) {
            $table->id();
            $table->foreignId('academic_year_id')->constrained()->onDelete('cascade');
            $table->string('name'); // Ex: "Trimestre 1", "Semestre 1"
            $table->enum('type', ['trimester', 'semester'])->default('trimester');
            $table->integer('number'); // 1, 2, 3 pour trimestre ou 1, 2 pour semestre
            $table->date('start_date');
            $table->date('end_date');
            $table->boolean('is_current')->default(false);
            $table->text('description')->nullable();
            $table->timestamps();
            $table->softDeletes();

            // Indexes
            $table->index('academic_year_id');
            $table->index('is_current');
            $table->index(['academic_year_id', 'number']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('semesters');
    }
};
