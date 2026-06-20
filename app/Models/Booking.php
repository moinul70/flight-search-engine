<?php

namespace App\Models;

use App\Models\Passenger;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Booking extends Model
{
    protected $fillable = [
        'reference_id', 'flight_identifier', 'carrier', 
        'flight_no', 'provider_used', 'total_price', 'passenger_details'
    ];

    protected $casts = [
        'passenger_details' => 'array'
    ];

    protected static function booted()
    {
        static::creating(function ($booking) {
            $booking->reference_id = 'BK-' . strtoupper(Str::random(8));
        });
    }
}
