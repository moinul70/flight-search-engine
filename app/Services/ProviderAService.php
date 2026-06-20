<?php

namespace App\Services;

use App\DTOs\FlightDTO;
use App\Contracts\ProviderInterface;
use Carbon\Carbon;

/**
 * Mock for Provider A.
 *
 * This is a mock provider that returns a static set of flights.
 * It's used for testing the aggregator and the booking engine.
 */
class ProviderAService implements ProviderInterface
{
    public function getName(): string
    {
        return 'provider_a';
    }

    public function search(string $from, string $to, Carbon $date): array
    {
        // Mocking client network call or local mock endpoint
        $response = [
            "flights" => [
                [ "carrier" => "AA", "from" => "DAC", "to" => "DXB", "depart" => "2026-07-01T08:00:00", "arrive" => "2026-07-01T12:30:00", "stops" => 0, "fare_usd" => 320.00, "flight_no" => "AA101" ],
                [ "carrier" => "AA", "from" => "DAC", "to" => "DXB", "depart" => "2026-07-01T22:10:00", "arrive" => "2026-07-02T02:40:00", "stops" => 0, "fare_usd" => 280.00, "flight_no" => "AA205" ],
                [ "carrier" => "BS", "from" => "DAC", "to" => "DXB", "depart" => "2026-07-01T09:15:00", "arrive" => "2026-07-01T15:00:00", "stops" => 1, "fare_usd" => 310.00, "flight_no" => "BS220" ],
                [ "carrier" => "EK", "from" => "DAC", "to" => "DXB", "depart" => "2026-07-01T03:45:00", "arrive" => "2026-07-01T06:50:00", "stops" => 0, "fare_usd" => 410.00, "flight_no" => "EK585" ]
            ]
        ];

        return collect($response['flights'])
            ->filter(fn($f) => $f['from'] === $from && $f['to'] === $to && Carbon::parse($f['depart'])->isSameDay($date))
            ->map(function($f) {
                $dep = Carbon::parse($f['depart']);
                return new FlightDTO(
                    id: FlightDTO::generateId($f['carrier'], $f['flight_no'], $dep),
                    carrier: $f['carrier'],
                    flightNo: $f['flight_no'],
                    from: $f['from'],
                    to: $f['to'],
                    departureTime: $dep,
                    arrivalTime: Carbon::parse($f['arrive']),
                    stops: $f['stops'],
                    bestPrice: (float)$f['fare_usd'],
                    currency: 'USD',
                    bestProvider: $this->getName(),
                    allOffers: [['provider' => $this->getName(), 'price' => (float)$f['fare_usd']]]
                );
            })->toArray();
    }
}