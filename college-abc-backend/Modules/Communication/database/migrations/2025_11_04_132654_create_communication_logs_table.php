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
        Schema::create('communication_logs', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('channel'); // email, sms, push, in_app
            $table->string('provider')->nullable(); // smtp, twilio, firebase, etc.
            $table->string('recipient_type')->nullable(); // App\Models\User, etc.
            $table->uuid('recipient_id')->nullable();
            $table->string('recipient_address'); // email, phone, device_token
            $table->string('template_name')->nullable();
            $table->string('subject')->nullable();
            $table->text('content');
            $table->json('variables')->nullable();
            $table->string('status')->default('pending'); // pending, processing, sent, delivered, failed, cancelled
            $table->text('error_message')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->timestamp('delivered_at')->nullable();
            $table->timestamp('opened_at')->nullable();
            $table->timestamp('clicked_at')->nullable();
            $table->json('metadata')->nullable();
            $table->string('priority')->default('normal'); // low, normal, high, urgent
            $table->integer('attempts')->default(0);
            $table->integer('max_attempts')->default(3);
            $table->timestamp('next_retry_at')->nullable();
            $table->string('batch_id')->nullable();
            $table->foreignUuid('user_id')->nullable()->constrained('users')->onDelete('set null');

            $table->timestamps();
            $table->softDeletes();

            // Indexes
            $table->index(['channel', 'status']);
            $table->index(['recipient_type', 'recipient_id']);
            $table->index(['template_name']);
            $table->index(['batch_id']);
            $table->index(['status', 'next_retry_at']);
            $table->index(['created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('communication_logs');
    }
};
