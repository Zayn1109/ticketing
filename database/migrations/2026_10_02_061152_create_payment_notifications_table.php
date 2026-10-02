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
        Schema::create('payment_notifications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('payment_id')->nullable()->constrained()->restrictOnDelete();

            $table->string('gateway', 20);
            $table->string('merchant_trade_no', 20)->nullable();
            $table->string('idempotency_key', 64)->nullable()->unique();

            $table->text('raw_payload');
            $table->boolean('signature_valid');
            $table->string('source_ip', 45)->nullable();

            $table->enum('status', ['pending', 'processed', 'unmatched', 'invalid_signature', 'failed'])->default('pending');
            $table->string('error_message')->nullable();

            $table->timestamp('received_at');
            $table->timestamp('processed_at')->nullable();
            $table->timestamps();

            $table->index('merchant_trade_no');
            $table->index(['status', 'received_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payment_notifications');
    }
};
