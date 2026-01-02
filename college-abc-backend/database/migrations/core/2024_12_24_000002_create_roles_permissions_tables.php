<?php

/**
 * Migration: Création des tables roles et permissions
 * Base de données: school_core (centrale)
 * 
 * Système de rôles et permissions granulaires
 * Conforme au cahier des charges Burkina Faso
 */

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $connection = 'school_core';

    public function up(): void
    {
        // Table des rôles
        if (!Schema::connection($this->connection)->hasTable('roles')) Schema::connection($this->connection)->create('roles', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name', 50)->unique(); // direction, secretariat, comptabilite, enseignant, parent, eleve
            $table->string('display_name', 100);
            $table->text('description')->nullable();
            $table->boolean('is_system')->default(false); // Non modifiable si true
            $table->timestamps();
        });

        // Table des permissions
        if (!Schema::connection($this->connection)->hasTable('permissions')) Schema::connection($this->connection)->create('permissions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name', 100)->unique(); // Format: module.action
            $table->string('display_name', 150);
            $table->string('module', 50)->index(); // Module concerné
            $table->text('description')->nullable();
            $table->timestamps();
        });

        // Table pivot role_permissions
        if (!Schema::connection($this->connection)->hasTable('role_permissions')) Schema::connection($this->connection)->create('role_permissions', function (Blueprint $table) {
            $table->uuid('role_id');
            $table->uuid('permission_id');
            $table->timestamps();

            $table->primary(['role_id', 'permission_id']);
            $table->foreign('role_id')->references('id')->on('roles')->onDelete('cascade');
            $table->foreign('permission_id')->references('id')->on('permissions')->onDelete('cascade');
        });

        // Table pivot user_roles (multi-rôles possible)
        if (!Schema::connection($this->connection)->hasTable('user_roles')) Schema::connection($this->connection)->create('user_roles', function (Blueprint $table) {
            $table->uuid('user_id');
            $table->uuid('role_id');
            $table->timestamps();

            $table->primary(['user_id', 'role_id']);
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('role_id')->references('id')->on('roles')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::connection($this->connection)->dropIfExists('user_roles');
        Schema::connection($this->connection)->dropIfExists('role_permissions');
        Schema::connection($this->connection)->dropIfExists('permissions');
        Schema::connection($this->connection)->dropIfExists('roles');
    }
};
