<?php

/**
 * Migration: Création des tables centrales
 * Base de données: school_core (centrale)
 * 
 * Tables: school_years, configurations, audit_logs, notifications
 */

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $connection = 'school_core';

    public function up(): void
    {
        // Table des années scolaires
        if (!Schema::connection($this->connection)->hasTable('school_years')) Schema::connection($this->connection)->create('school_years', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name', 20)->unique(); // Format: 2024-2025
            $table->date('start_date');
            $table->date('end_date');
            $table->boolean('is_current')->default(false)->index();
            $table->boolean('is_locked')->default(false);
            $table->timestamps();
        });

        // Table des configurations système
        if (!Schema::connection($this->connection)->hasTable('configurations')) Schema::connection($this->connection)->create('configurations', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('key', 100)->unique();
            $table->text('value')->nullable(); // JSON
            $table->enum('type', ['string', 'number', 'boolean', 'json'])->default('string');
            $table->string('category', 50)->index();
            $table->boolean('is_public')->default(false);
            $table->text('description')->nullable();
            $table->timestamps();
        });

        // Table d'audit logs (traçabilité complète)
        if (!Schema::connection($this->connection)->hasTable('audit_logs')) Schema::connection($this->connection)->create('audit_logs', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('user_id')->nullable()->index();
            $table->string('action', 50)->index(); // create, update, delete, login, logout, etc.
            $table->string('model_type', 100)->nullable()->index();
            $table->uuid('model_id')->nullable()->index();
            $table->json('old_values')->nullable();
            $table->json('new_values')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->string('url', 500)->nullable();
            $table->string('method', 10)->nullable();
            $table->timestamp('created_at')->index();

            $table->foreign('user_id')->references('id')->on('users')->nullOnDelete();

            // Index composite pour les requêtes de filtrage
            $table->index(['model_type', 'model_id']);
            $table->index(['user_id', 'action', 'created_at']);
        });

        // Table des notifications
        if (!Schema::connection($this->connection)->hasTable('notifications')) Schema::connection($this->connection)->create('notifications', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('user_id')->index();
            $table->enum('type', ['sms', 'email', 'push', 'internal'])->index();
            $table->string('title', 200);
            $table->text('content');
            $table->json('data')->nullable();
            $table->boolean('is_read')->default(false)->index();
            $table->timestamp('read_at')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->boolean('is_sent')->default(false);
            $table->text('error_message')->nullable();
            $table->timestamps();

            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');

            // Index pour les requêtes fréquentes
            $table->index(['user_id', 'is_read', 'created_at']);
        });

        // Table d'archivage des sessions pour sécurité
        if (!Schema::connection($this->connection)->hasTable('user_sessions')) Schema::connection($this->connection)->create('user_sessions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('user_id')->index();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->text('payload');
            $table->integer('last_activity')->index();
            $table->timestamp('created_at');

            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::connection($this->connection)->dropIfExists('user_sessions');
        Schema::connection($this->connection)->dropIfExists('notifications');
        Schema::connection($this->connection)->dropIfExists('audit_logs');
        Schema::connection($this->connection)->dropIfExists('configurations');
        Schema::connection($this->connection)->dropIfExists('school_years');
    }
};
