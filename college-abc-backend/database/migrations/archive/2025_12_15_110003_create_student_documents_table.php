<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    { if (!Schema::hasTable('student_documents')) Schema::create('student_documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained()->onDelete('cascade');
            $table->enum('type', ['birth_certificate', 'medical_certificate', 'photo', 'transcript', 'other']);
            $table->string('title');
            $table->string('file_path');
            $table->string('file_name');
            $table->integer('file_size')->nullable(); // en KB
            $table->date('issue_date')->nullable();
            $table->date('expiry_date')->nullable();
            $table->text('description')->nullable();
            $table->timestamps();

            $table->index('student_id');
            $table->index('type');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('student_documents');
    }
};
