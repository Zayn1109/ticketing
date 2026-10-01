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
        Schema::create('ticket_types', function (Blueprint $table) {
            $table->id();
            $table->foreignId('event_session_id')->constrained()->restrictOnDelete();
            $table->string('name');
            $table->unsignedInteger('price');
            $table->unsignedInteger('quota');
            $table->timestamp('sale_start_at');
            $table->timestamp('sale_end_at');
            $table->unsignedSmallInteger('max_per_user')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ticket_types');
    }
};
