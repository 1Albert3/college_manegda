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
        Schema::create('parent_student', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained('students')->onDelete('cascade');
            $table->foreignUuid('parent_id')->constrained('users')->onDelete('cascade'); // Users use UUID
            $table->string('relationship')->default('parent'); // father, mother, guardian...
            $table->boolean('is_primary')->default(false);
            $table->timestamps();

            $table->unique(['student_id', 'parent_id']); // Prevent duplicate links
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('parent_student');
    }
};
