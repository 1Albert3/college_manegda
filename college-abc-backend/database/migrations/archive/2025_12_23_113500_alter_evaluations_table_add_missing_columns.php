<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('evaluations', function (Blueprint $table) {
            // Rename existing columns to match model expectations
            if (Schema::hasColumn('evaluations', 'date') && !Schema::hasColumn('evaluations', 'evaluation_date')) {
                $table->renameColumn('date', 'evaluation_date');
            }
            if (Schema::hasColumn('evaluations', 'max_score') && !Schema::hasColumn('evaluations', 'maximum_score')) {
                $table->renameColumn('max_score', 'maximum_score');
            }
        });

        Schema::table('evaluations', function (Blueprint $table) {
            // Add missing columns that the model expects
        if (!Schema::hasColumn('evaluations', 'code')) {
                $table->string('code', 50)->nullable()->after('title');
            }
            if (!Schema::hasColumn('evaluations', 'period')) {
                $table->string('period', 50)->nullable()->after('type');
            }
            if (!Schema::hasColumn('evaluations', 'status')) {
                $table->enum('status', ['planned', 'ongoing', 'completed', 'cancelled'])->default('planned')->after('coefficient');
            }
            if (!Schema::hasColumn('evaluations', 'weight_percentage')) {
                $table->decimal('weight_percentage', 5, 2)->default(0)->after('coefficient');
            }
            if (!Schema::hasColumn('evaluations', 'minimum_score')) {
                $table->decimal('minimum_score', 5, 2)->default(0)->after('maximum_score');
            }
            if (!Schema::hasColumn('evaluations', 'grading_criteria')) {
                $table->json('grading_criteria')->nullable()->after('description');
            }
            if (!Schema::hasColumn('evaluations', 'comments')) {
                $table->text('comments')->nullable()->after('grading_criteria');
            }

            // Make academic_year_id nullable since it can be auto-assigned
            // Check if constraint exists first - this may need adjustment based on DB type
        });

        // Make foreign key nullable
        Schema::table('evaluations', function (Blueprint $table) {
            $table->foreignId('academic_year_id')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('evaluations', function (Blueprint $table) {
            // Revert column renames
            if (Schema::hasColumn('evaluations', 'evaluation_date')) {
                $table->renameColumn('evaluation_date', 'date');
            }
            if (Schema::hasColumn('evaluations', 'maximum_score')) {
                $table->renameColumn('maximum_score', 'max_score');
            }

            // Drop added columns
            $columnsToDrop = ['code', 'period', 'status', 'weight_percentage', 'minimum_score', 'grading_criteria', 'comments'];
            foreach ($columnsToDrop as $column) {
                if (Schema::hasColumn('evaluations', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
