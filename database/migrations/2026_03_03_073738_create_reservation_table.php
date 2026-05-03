<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reservations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('event_id')->constrained('events')->onDelete('cascade');
            $table->foreignId('seat_id')->constrained('seats')->onDelete('cascade');
            $table->enum('status', ['confirmed', 'cancelled', 'checked_in'])->default('confirmed');
            $table->enum('type', ['regular', 'vip_guest'])->default('regular');
            $table->string('qr_code')->unique()->nullable();
            $table->timestamp('checked_in_at')->nullable();
            $table->timestamps();

            $table->unique(['event_id', 'seat_id'], 'unique_event_seat');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reservations');
    }
};
