<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('enrollments', function (Blueprint $table) {
            // Rename classroom_id to class_room_id for consistency
            $table->renameColumn('classroom_id', 'class_room_id');
            
            // Add missing columns
            $table->foreignId('academic_year_id')->nullable()->after('class_room_id')->constrained();
            $table->date('enrollment_date')->nullable()->after('academic_year_id');
            $table->decimal('discount_percentage', 5, 2)->default(0)->after('enrollment_date');
            $table->text('notes')->nullable()->after('status');
            $table->softDeletes();
            
            // Update year column to be nullable (will use academic_year_id instead)
            $table->string('year')->nullable()->change();
            
            // Add indexes
            $table->index('status');
            $table->index('enrollment_date');
        });
    }

    public function down(): void
    {
        Schema::table('enrollments', function (Blueprint $table) {
            $table->renameColumn('class_room_id', 'classroom_id');
            $table->dropSoftDeletes();
            $table->dropForeign(['academic_year_id']);
            $table->dropColumn(['academic_year_id', 'enrollment_date', 'discount_percentage', 'notes']);
            $table->dropIndex(['enrollments_status_index']);
            $table->dropIndex(['enrollments_enrollment_date_index']);
            $table->string('year')->nullable(false)->change();
        });
    }
};
