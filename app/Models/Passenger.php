<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

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
