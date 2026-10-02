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
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->restrictOnDelete();
            $table->string('merchant_trade_no', 20)->unique();
            $table->string('gateway', 20);
            $table->enum('status', ['pending', 'success', 'failed'])->default('pending');
            $table->unsignedInteger('amount');
            $table->string('gateway_trade_no', 64)->nullable();
            $table->unsignedInteger('paid_amount')->nullable();
            $table->string('payment_method', 30)->nullable();
            $table->string('card_last_four', 4)->nullable();

            $table->string('failure_code', 30)->nullable();
            $table->string('failure_message')->nullable();
            $table->timestamp('initiated_at');
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            $table->index(['order_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
