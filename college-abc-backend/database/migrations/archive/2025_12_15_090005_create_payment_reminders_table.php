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
        if (!Schema::hasTable('payment_reminders')) Schema::create('payment_reminders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('invoice_id')->constrained('invoices')->onDelete('cascade');
            $table->foreignUuid('student_id')->constrained('students')->onDelete('cascade');
            $table->enum('type', ['sms', 'email', 'notification'])->default('sms');
            $table->text('message'); // Contenu du rappel
            $table->date('reminder_date'); // Date d'envoi du rappel
            $table->enum('status', ['planifie', 'envoye', 'echoue', 'annule'])->default('planifie');
            $table->timestamp('sent_at')->nullable();
            $table->text('error_message')->nullable(); // Message d'erreur si échec
            $table->integer('attempt_count')->default(0); // Nombre de tentatives
            $table->timestamps();

            // Indexes
            $table->index('invoice_id');
            $table->index('student_id');
            $table->index('status');
            $table->index('reminder_date');
            $table->index(['status', 'reminder_date']); // Pour les jobs automatiques
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payment_reminders');
    }
};
