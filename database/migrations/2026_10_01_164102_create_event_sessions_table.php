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
        Schema::create('event_sessions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('event_id')->constrained()->cascadeOnDelete();
            $table->string('name')->nullable();
            $table->string('venue_name');
            $table->string('venue_address')->nullable();
            $table->timestamp('starts_at');
            $table->timestamp('ends_at');
            $table->timestamp('doors_open_at')->nullable();
            $table->string('timezone', 64)->default('Asia/Taipei');
            $table->unsignedInteger('capacity')->nullable();
            $table->enum('status', ['scheduled', 'cancelled'])->default('scheduled');
            $table->timestamp('cancelled_at')->nullable();
            $table->foreignId('cancelled_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('cancellation_reason')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('event_sessions');
    }
};
