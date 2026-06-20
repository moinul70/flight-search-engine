<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BookingResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
       return [
            'booking_reference' => $this->reference_id,
            'flight_identifier' => $this->flight_identifier,
            'details' => [
                'carrier' => $this->carrier,
                'flight_no' => $this->flight_no,
                'fulfilled_by' => $this->provider_used,
            ],
            'billing' => [
                'total_paid' => (float) $this->total_price,
                'currency' => 'USD',
            ],
            'passengers' => $this->passenger_details,
            'created_at' => $this->created_at->toIso8601String(),
        ];
    }
}
