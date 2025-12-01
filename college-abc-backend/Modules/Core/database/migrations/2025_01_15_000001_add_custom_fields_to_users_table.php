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
        Schema::table('users', function (Blueprint $table) {
            $table->string('phone')->nullable()->after('password');
            $table->string('role_type')->default('student')->after('phone');
            $table->boolean('is_active')->default(true)->after('role_type');
            $table->timestamp('last_login_at')->nullable()->after('is_active');
            $table->nullableMorphs('profile'); // Creates profile_id and profile_type columns
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'phone',
                'role_type',
                'is_active',
                'last_login_at',
                'profile_id',
                'profile_type'
            ]);
        });
    }
};

