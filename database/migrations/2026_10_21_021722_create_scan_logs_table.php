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
        Schema::create('scan_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('event_session_id')->constrained()->restrictOnDelete();
            $table->foreignId('ticket_id')->nullable()->constrained()->restrictOnDelete();
            $table->foreignId('scanned_by')->contrained('users')->restrictOnDelete();

            $table->string('raw_code');
            $table->enum('result', [
                'success',
                'already_checked_in',
                'not_found',
                'wrong_session',
                'voided',
                'not_open',
                'event_ended',
                'invalid_signature',
            ]);

            $table->string('gate', 50)->nullable();
            $table->string('device_id', 64)->nullable();

            $table->timestamp('scanned_at');
            $table->timestamps();

            $table->index(['event_session_id', 'scanned_at']);
            $table->index(['result', 'scanned_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('scan_logs');
    }
};
