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
        Schema::create('tickets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_item_id')->constrained()->restrictOnDelete();
            $table->ulid('code')->unique();

            $table->string('holder_name', 50);
            $table->char('holder_id_last4', 4);

            $table->timestamp('issued_at');

            $table->timestamp('checked_in_at')->nullable();
            $table->foreignId('checked_in_by')->nullable()->constrained('users')->restrictOnDelete();
            $table->string('checked_in_gate', 50)->nullable();

            $table->timestamp('voided_at')->nullable();
            $table->enum('void_reason', ['refunded', 'event_cancelled', 'organizer_revoked'])->nullable();
            $table->foreignId('voided_by')->nullable()->constrained('users')->restrictOnDelete();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tickets');
    }
};
