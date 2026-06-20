<?php

namespace App\Models;

use App\Models\Passenger;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Booking extends Model
{
    protected $fillable = [
        'reference',
        'flight_id',
        'carrier',
        'flight_number',
        'origin',
        'destination',
        'departure_at',
        'arrival_at',
        'stops',
        'price_usd',
        'status',
    ];
 
    protected $casts = [
        'departure_at' => 'datetime',
        'arrival_at'   => 'datetime',
        'price_usd'    => 'decimal:2',
        'stops'        => 'integer',
    ];
 
    public function passengers(): HasMany
    {
        return $this->hasMany(Passenger::class);
    }
}
