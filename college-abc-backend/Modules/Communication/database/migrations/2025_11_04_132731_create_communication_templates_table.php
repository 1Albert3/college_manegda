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
        Schema::create('communication_templates', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->string('channel'); // email, sms, push, in_app
            $table->string('subject')->nullable(); // For email templates
            $table->text('content'); // Plain text content
            $table->text('html_content')->nullable(); // HTML content for emails
            $table->json('variables')->nullable(); // Available variables
            $table->boolean('is_active')->default(false);
            $table->string('category')->nullable();
            $table->string('priority')->default('normal'); // low, normal, high, urgent
            $table->json('metadata')->nullable();
            $table->foreignUuid('created_by')->nullable()->constrained('users')->onDelete('set null');
            $table->foreignUuid('updated_by')->nullable()->constrained('users')->onDelete('set null');

            $table->timestamps();

            // Indexes
            $table->index(['channel', 'is_active']);
            $table->index(['category']);
            $table->index(['priority']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('communication_templates');
    }
};
