<?php

namespace App\Models;

use App\Models\Booking;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Passenger extends Model
{
     protected $fillable = [
        'booking_id',
        'first_name',
        'last_name',
        'dob',
        'passport',
    ];
 
    protected $casts = [
        'dob' => 'date',
    ];
 
    public function booking(): BelongsTo
    {
        return $this->belongsTo(Booking::class);
    }
}
