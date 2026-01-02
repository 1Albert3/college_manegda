<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // MP
        if (Schema::connection('school_mp')->hasTable('enrollments_mp')) {
            Schema::connection('school_mp')->table('enrollments_mp', function (Blueprint $table) {
                if (!Schema::connection('school_mp')->hasColumn('enrollments_mp', 'montant_paye')) {
                    $table->decimal('montant_paye', 12, 2)->default(0)->after('montant_final');
                }
                if (!Schema::connection('school_mp')->hasColumn('enrollments_mp', 'solde_restant')) {
                    $table->decimal('solde_restant', 12, 2)->default(0)->after('montant_paye');
                }
                if (!Schema::connection('school_mp')->hasColumn('enrollments_mp', 'prochaine_echeance')) {
                    $table->date('prochaine_echeance')->nullable()->after('solde_restant');
                }
            });
        }

        // College
        if (Schema::connection('school_college')->hasTable('enrollments_college')) {
            Schema::connection('school_college')->table('enrollments_college', function (Blueprint $table) {
                if (!Schema::connection('school_college')->hasColumn('enrollments_college', 'montant_paye')) {
                    $table->decimal('montant_paye', 12, 2)->default(0)->after('montant_final');
                }
                if (!Schema::connection('school_college')->hasColumn('enrollments_college', 'solde_restant')) {
                    $table->decimal('solde_restant', 12, 2)->default(0)->after('montant_paye');
                }
                if (!Schema::connection('school_college')->hasColumn('enrollments_college', 'prochaine_echeance')) {
                    $table->date('prochaine_echeance')->nullable()->after('solde_restant');
                }
            });
        }

        // Lycee
        if (Schema::connection('school_lycee')->hasTable('enrollments_lycee')) {
            Schema::connection('school_lycee')->table('enrollments_lycee', function (Blueprint $table) {
                if (!Schema::connection('school_lycee')->hasColumn('enrollments_lycee', 'montant_paye')) {
                    $table->decimal('montant_paye', 12, 2)->default(0)->after('montant_final');
                }
                if (!Schema::connection('school_lycee')->hasColumn('enrollments_lycee', 'solde_restant')) {
                    $table->decimal('solde_restant', 12, 2)->default(0)->after('montant_paye');
                }
                if (!Schema::connection('school_lycee')->hasColumn('enrollments_lycee', 'prochaine_echeance')) {
                    $table->date('prochaine_echeance')->nullable()->after('solde_restant');
                }
            });
        }
    }

    public function down(): void
    {
        // MP
        if (Schema::connection('school_mp')->hasTable('enrollments_mp')) {
            Schema::connection('school_mp')->table('enrollments_mp', function (Blueprint $table) {
                $table->dropColumn(['montant_paye', 'solde_restant', 'prochaine_echeance']);
            });
        }
        // College
        if (Schema::connection('school_college')->hasTable('enrollments_college')) {
            Schema::connection('school_college')->table('enrollments_college', function (Blueprint $table) {
                $table->dropColumn(['montant_paye', 'solde_restant', 'prochaine_echeance']);
            });
        }
        // Lycee
        if (Schema::connection('school_lycee')->hasTable('enrollments_lycee')) {
            Schema::connection('school_lycee')->table('enrollments_lycee', function (Blueprint $table) {
                $table->dropColumn(['montant_paye', 'solde_restant', 'prochaine_echeance']);
            });
        }
    }
};
