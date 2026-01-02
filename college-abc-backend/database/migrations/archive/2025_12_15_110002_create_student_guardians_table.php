<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    { if (!Schema::hasTable('student_guardians')) Schema::create('student_guardians', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained()->onDelete('cascade');
            $table->enum('relationship', ['father', 'mother', 'guardian', 'uncle', 'aunt', 'grandparent', 'other']);
            $table->string('first_name');
            $table->string('last_name');
            $table->string('phone');
            $table->string('email')->nullable();
            $table->string('profession')->nullable();
            $table->string('address')->nullable();
            $table->boolean('is_primary')->default(false); // Contact principal
            $table->boolean('can_pick_up')->default(true); // Autorisé à récupérer l'élève
            $table->timestamps();

            $table->index('student_id');
            $table->index('is_primary');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('student_guardians');
    }
};
