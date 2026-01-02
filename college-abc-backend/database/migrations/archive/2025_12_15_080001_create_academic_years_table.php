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
    { if (!Schema::hasTable('academic_years')) Schema::create('academic_years', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique(); // Ex: "2024-2025"
            $table->date('start_date');
            $table->date('end_date');
            $table->boolean('is_current')->default(false);
            $table->text('description')->nullable();
            $table->timestamps();
            $table->softDeletes();

            // Indexes
            $table->index('is_current');
            $table->index('start_date');
            $table->index('end_date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('academic_years');
    }
};
