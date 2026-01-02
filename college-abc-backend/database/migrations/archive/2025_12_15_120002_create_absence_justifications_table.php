<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    { if (!Schema::hasTable('absence_justifications')) Schema::create('absence_justifications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('attendance_id')->constrained()->onDelete('cascade');
            $table->enum('type', ['medical', 'family', 'official', 'other']);
            $table->text('reason');
            $table->string('document_path')->nullable();
            $table->date('submitted_date');
            $table->enum('status', ['pending', 'approved', 'rejected'])->default('pending');
            $table->foreignUuid('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->text('review_notes')->nullable();
            $table->timestamps();

            $table->index('status');
            $table->index('submitted_date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('absence_justifications');
    }
};
