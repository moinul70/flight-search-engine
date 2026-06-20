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
        Schema::create('bookings', function (Blueprint $table) {
            $table->id();
            $table->string('reference', 16)->unique();   // e.g. BK-A1B2C3D4
            $table->string('flight_id');                 // canonical fingerprint
            $table->string('carrier', 3);
            $table->string('flight_number', 10);
            $table->char('origin', 3);
            $table->char('destination', 3);
            $table->dateTime('departure_at');
            $table->dateTime('arrival_at');
            $table->unsignedTinyInteger('stops');
            $table->decimal('price_usd', 10, 2);
            $table->string('status', 20)->default('confirmed');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bookings');
    }
};
