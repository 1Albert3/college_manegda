<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    { if (!Schema::hasTable('books')) Schema::create('books', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('author')->nullable();
            $table->string('isbn', 20)->nullable()->unique();
            $table->string('category', 50)->index();
            $table->string('publisher', 100)->nullable();
            $table->integer('published_year')->nullable();
            $table->integer('total_copies')->default(1);
            $table->integer('available_copies')->default(1);
            $table->string('cover_image')->nullable();
            $table->string('location')->nullable(); // Rayon/Etagère
            $table->enum('status', ['active', 'damaged', 'lost', 'removed'])->default('active');
            $table->timestamps();
        }); if (!Schema::hasTable('book_loans')) Schema::create('book_loans', function (Blueprint $table) {
            $table->id();
            $table->foreignId('book_id')->constrained()->onDelete('cascade');
            $table->uuid('user_id')->index(); // Emprunteur (Elève ou Prof)
            $table->date('loan_date');
            $table->date('due_date');
            $table->date('return_date')->nullable();
            $table->enum('status', ['active', 'returned', 'overdue', 'lost'])->default('active');
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('book_loans');
        Schema::dropIfExists('books');
    }
};
