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
            
            // Unique tracking alphanumeric reference
            $table->string('reference_id', 12)->unique();
            
            // Base64 composite stable string identifier
            $table->string('flight_identifier', 255);
            
            // Indexed values for high-performance analytical queries and operational lookups
            $table->string('carrier', 3)->index();
            $table->string('flight_no', 10);
            $table->string('provider_used', 50);
            
            // Standard financial scale precision mapping (e.g., 999999.99 max)
            $table->decimal('total_price', 8, 2);
            
            // Native MySQL JSON column for fast payload queries
            $table->json('passenger_details');
            
            $table->timestamps();
            
            // Composite index optimization for operational back-office dashboards
            $table->index(['carrier', 'flight_no']);
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
