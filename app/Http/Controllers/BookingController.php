<?php

namespace App\Http\Controllers;

use App\Http\Resources\BookingResource;
use App\Models\Booking;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class BookingController extends Controller
{
   public function store(Request $request): BookingResource
    {
       
       $validated = $request->validate([
            'flight_identifier' => 'required|string',
            'provider' => 'required|string',
            'passengers' => 'required|array|min:1',
            'passengers.*.name' => 'required|string',
            'passengers.*.passport' => 'required|string',
        ]);

        $decoded = base64_decode($validated['flight_identifier']);
        if (!$decoded || !str_contains($decoded, '|')) {
            abort(422, 'Malformed or invalid flight tracking token identifier.');
        }

        [$carrier, $flightNo, $departure] = explode('|', $decoded);

        // Execute inside a MySQL atomic transaction transaction block
        $booking = DB::transaction(function () use ($validated, $carrier, $flightNo) {
            return Booking::create([
                'flight_identifier' => $validated['flight_identifier'],
                'carrier' => $carrier,
                'flight_no' => $flightNo,
                'provider_used' => $validated['provider'],
                'total_price' => 295.00 * count($validated['passengers']), // Multiplied placeholder allocation unit
                'passenger_details' => $validated['passengers']
            ]);
        });

        return new BookingResource($booking);
    }

    public function show(string $reference): BookingResource
    {
        $booking = Booking::where('reference_id', $reference)->firstOrFail();
        return new BookingResource($booking);
    }
}
