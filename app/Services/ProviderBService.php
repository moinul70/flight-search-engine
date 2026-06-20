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
class ProviderBService implements ProviderInterface
{
    public function getName(): string
    {
        return 'provider_b';
    }

   public function search(string $from, string $to, Carbon $date): array
    {
        $response = [
            "data" => [
                [ "airline_code" => "BS", "origin" => "DAC", "destination" => "DXB", "departure_time" => "2026-07-01 09:15", "arrival_time" => "2026-07-01 15:00", "segments" => 1, "price" => [ "amount" => 295, "currency" => "USD" ], "number" => "BS220" ],
                [ "airline_code" => "BS", "origin" => "DAC", "destination" => "DXB", "departure_time" => "2026-07-01 14:30", "arrival_time" => "2026-07-01 19:20", "segments" => 1, "price" => [ "amount" => 265, "currency" => "USD" ], "number" => "BS118" ],
                [ "airline_code" => "EK", "origin" => "DAC", "destination" => "DXB", "departure_time" => "2026-07-01 03:45", "arrival_time" => "2026-07-01 06:50", "segments" => 0, "price" => [ "amount" => 399, "currency" => "USD" ], "number" => "EK585" ]
            ]
        ];

        return collect($response['data'])
            ->filter(fn($f) => $f['origin'] === $from && $f['destination'] === $to && Carbon::parse($f['departure_time'])->isSameDay($date))
            ->map(function($f) {
                $dep = Carbon::parse($f['departure_time']);
                return new FlightDTO(
                    id: FlightDTO::generateId($f['airline_code'], $f['number'], $dep),
                    carrier: $f['airline_code'],
                    flightNo: $f['number'],
                    from: $f['origin'],
                    to: $f['destination'],
                    departureTime: $dep,
                    arrivalTime: Carbon::parse($f['arrival_time']),
                    stops: $f['segments'],
                    bestPrice: (float)$f['price']['amount'],
                    currency: $f['price']['currency'],
                    bestProvider: $this->getName(),
                    allOffers: [['provider' => $this->getName(), 'price' => (float)$f['price']['amount']]]
                );
            })->toArray();
    }
}