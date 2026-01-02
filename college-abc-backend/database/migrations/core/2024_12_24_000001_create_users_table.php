<?php

/**
 * Migration: Création de la table users
 * Base de données: school_core (centrale)
 * 
 * Table des utilisateurs du système avec support:
 * - Authentification sécurisée
 * - Double authentification (2FA)
 * - Verrouillage de compte
 * - Audit trail
 */

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Connexion à utiliser pour cette migration
     */
    protected $connection = 'school_core';

    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (!Schema::connection($this->connection)->hasTable('users')) Schema::connection($this->connection)->create('users', function (Blueprint $table) {
            // Clé primaire UUID
            $table->uuid('id')->primary();

            // Informations de connexion
            $table->string('email', 191)->unique();
            $table->string('password', 255);
            $table->string('phone', 20)->nullable()->index();

            // Informations personnelles
            $table->string('first_name', 100);
            $table->string('last_name', 100);
            $table->string('profile_photo', 255)->nullable();

            // Vérification email
            $table->timestamp('email_verified_at')->nullable();

            // Double Authentification (2FA)
            $table->text('two_factor_secret')->nullable();
            $table->boolean('two_factor_enabled')->default(false);
            $table->text('two_factor_recovery_codes')->nullable();

            // Statut du compte
            $table->boolean('is_active')->default(true)->index();
            $table->timestamp('last_login_at')->nullable();
            $table->string('last_login_ip', 45)->nullable();

            // Sécurité: verrouillage de compte
            $table->unsignedTinyInteger('failed_login_attempts')->default(0);
            $table->timestamp('locked_until')->nullable();

            // Rôle principal (pour compatibilité)
            $table->string('role', 50)->default('eleve')->index();

            // Token de rappel
            $table->rememberToken();

            // Timestamps et soft delete
            $table->timestamps();
            $table->softDeletes()->index();

            // Index composites pour les requêtes fréquentes
            $table->index(['email', 'is_active']);
            $table->index(['role', 'is_active']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::connection($this->connection)->dropIfExists('users');
    }
};
