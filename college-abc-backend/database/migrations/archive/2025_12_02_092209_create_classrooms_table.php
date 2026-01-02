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
    { if (!Schema::hasTable('classrooms')) Schema::create('classrooms', function (Blueprint $table) {
            $table->id();
            $table->foreignId('level_id')->constrained()->onDelete('cascade');
            $table->string('name');
            $table->integer('capacity')->default(50);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('classrooms');
    }
};
