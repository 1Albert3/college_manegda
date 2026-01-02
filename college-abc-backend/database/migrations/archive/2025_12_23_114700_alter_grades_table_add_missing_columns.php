<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('grades', function (Blueprint $table) {
            // Rename columns to match model expectations
            if (Schema::hasColumn('grades', 'comment') && !Schema::hasColumn('grades', 'comments')) {
                $table->renameColumn('comment', 'comments');
            }
            if (Schema::hasColumn('grades', 'graded_by') && !Schema::hasColumn('grades', 'recorded_by')) {
                $table->renameColumn('graded_by', 'recorded_by');
            }
            if (Schema::hasColumn('grades', 'graded_at') && !Schema::hasColumn('grades', 'recorded_at')) {
                $table->renameColumn('graded_at', 'recorded_at');
            }
        });

        Schema::table('grades', function (Blueprint $table) {
            // Add missing columns that the model expects
        if (!Schema::hasColumn('grades', 'coefficient')) {
                $table->decimal('coefficient', 3, 2)->default(1.00)->after('score');
            }
            if (!Schema::hasColumn('grades', 'grade_letter')) {
                $table->string('grade_letter', 2)->nullable()->after('weighted_score');
            }
            if (!Schema::hasColumn('grades', 'is_absent')) {
                $table->boolean('is_absent')->default(false)->after('grade_letter');
            }
            if (!Schema::hasColumn('grades', 'deleted_at')) {
                $table->softDeletes();
            }
        });

        // Make score nullable for absent students
        Schema::table('grades', function (Blueprint $table) {
            $table->decimal('score', 5, 2)->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('grades', function (Blueprint $table) {
            // Revert column renames
            if (Schema::hasColumn('grades', 'comments')) {
                $table->renameColumn('comments', 'comment');
            }
            if (Schema::hasColumn('grades', 'recorded_by')) {
                $table->renameColumn('recorded_by', 'graded_by');
            }
            if (Schema::hasColumn('grades', 'recorded_at')) {
                $table->renameColumn('recorded_at', 'graded_at');
            }

            // Drop added columns
            $columnsToDrop = ['coefficient', 'grade_letter', 'is_absent'];
            foreach ($columnsToDrop as $column) {
                if (Schema::hasColumn('grades', $column)) {
                    $table->dropColumn($column);
                }
            }

            if (Schema::hasColumn('grades', 'deleted_at')) {
                $table->dropSoftDeletes();
            }
        });
    }
};
