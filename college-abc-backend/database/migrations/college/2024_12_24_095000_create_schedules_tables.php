<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migration: Table des emplois du temps (schedules)
 */
return new class extends Migration
{
    public function up(): void
    {
        // Table pour Collège
        if (!Schema::connection('school_college')->hasTable('schedules')) {
            Schema::connection('school_college')->create('schedules', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->uuid('class_id');
                $table->uuid('subject_id');
                $table->uuid('teacher_id')->nullable();
                $table->uuid('school_year_id');

                // Jour et horaires
                $table->tinyInteger('day_number'); // 1=Lundi, 6=Samedi
                $table->string('day_name', 20);
                $table->time('start_time');
                $table->time('end_time');

                // Salle
                $table->string('room', 50)->nullable();

                // Métadonnées
                $table->enum('type', ['cours', 'tp', 'td', 'evaluation'])->default('cours');
                $table->boolean('is_published')->default(false);
                $table->text('notes')->nullable();

                $table->timestamps();

                // Index
                $table->index(['class_id', 'school_year_id']);
                $table->index(['teacher_id', 'school_year_id']);
                $table->index(['day_number', 'start_time']);

                // Contrainte unique: pas de doublon pour une classe sur un créneau
                $table->unique(['class_id', 'school_year_id', 'day_number', 'start_time'], 'unique_class_slot');
            });
        }

        // Table pour MP (Maternelle/Primaire)
        if (!Schema::connection('school_mp')->hasTable('schedules')) {
            Schema::connection('school_mp')->create('schedules', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->uuid('class_id');
                $table->uuid('subject_id')->nullable(); // Nullable car peut être activité générale
                $table->uuid('teacher_id')->nullable();
                $table->uuid('school_year_id');

                $table->tinyInteger('day_number');
                $table->string('day_name', 20);
                $table->time('start_time');
                $table->time('end_time');

                $table->string('room', 50)->nullable();
                $table->string('activity', 100)->nullable(); // Pour maternelle: "Activités motrices", etc.
                $table->enum('type', ['cours', 'activite', 'recreation', 'sieste'])->default('cours');
                $table->boolean('is_published')->default(false);

                $table->timestamps();

                $table->index(['class_id', 'school_year_id']);
                $table->unique(['class_id', 'school_year_id', 'day_number', 'start_time'], 'unique_class_slot');
            });
        }

        // Table pour Lycée
        if (!Schema::connection('school_lycee')->hasTable('schedules')) {
            Schema::connection('school_lycee')->create('schedules', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->uuid('class_id');
                $table->uuid('subject_id');
                $table->uuid('teacher_id')->nullable();
                $table->uuid('school_year_id');

                $table->tinyInteger('day_number');
                $table->string('day_name', 20);
                $table->time('start_time');
                $table->time('end_time');

                $table->string('room', 50)->nullable();
                $table->enum('type', ['cours', 'tp', 'td', 'evaluation', 'orientation'])->default('cours');
                $table->boolean('is_published')->default(false);
                $table->text('notes')->nullable();

                $table->timestamps();

                $table->index(['class_id', 'school_year_id']);
                $table->index(['teacher_id', 'school_year_id']);
                $table->unique(['class_id', 'school_year_id', 'day_number', 'start_time'], 'unique_class_slot');
            });
        }

        // Table des indisponibilités enseignants (On la met dans school_core car centrale ?)
        // Dans le seeder, elle n'est pas utilisée.
        // On va la mettre dans school_core par défaut.
        if (!Schema::hasTable('teacher_unavailabilities')) Schema::create('teacher_unavailabilities', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('teacher_id');
            $table->uuid('school_year_id');

            $table->tinyInteger('day_number');
            $table->time('start_time');
            $table->time('end_time');
            $table->string('reason', 200)->nullable();
            $table->boolean('recurring')->default(true); // Chaque semaine
            $table->date('specific_date')->nullable(); // Si ponctuel

            $table->timestamps();

            $table->index(['teacher_id', 'school_year_id']);
        });
    }

    public function down(): void
    {
        Schema::connection('school_lycee')->dropIfExists('schedules');
        Schema::connection('school_mp')->dropIfExists('schedules');
        Schema::connection('school_college')->dropIfExists('schedules');
        Schema::dropIfExists('teacher_unavailabilities');
    }
};
