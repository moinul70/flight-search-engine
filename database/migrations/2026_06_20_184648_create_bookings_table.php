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
            $table->string('reference_id')->unique();
            $table->string('flight_identifier'); // Stable ID passed back from search
            $table->string('carrier');
            $table->string('flight_no');
            $table->string('provider_used');
            $table->decimal('total_price', 8, 2);
            $table->json('passenger_details');
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
