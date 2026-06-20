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
class ProviderCService implements ProviderInterface
{
    public function getName(): string
    {
        return 'provider_c';
    }

  public function search(string $from, string $to, Carbon $date): array
    {
        $response = [
            "results" => [
                [ "iata" => "AA", "route" => [ "src" => "DAC", "dst" => "DXB" ], "times" => [ "dep" => 1782892800, "arr" => 1782909000 ], "layovers" => 0, "total_price" => 335, "currency" => "USD", "code" => "AA101" ],
                [ "iata" => "CJ", "route" => [ "src" => "DAC", "dst" => "DXB" ], "times" => [ "dep" => 1782885600, "arr" => 1782903600 ], "layovers" => 2, "total_price" => 270, "currency" => "USD", "code" => "CJ300" ],
                [ "iata" => "EK", "route" => [ "src" => "DAC", "dst" => "DXB" ], "times" => [ "dep" => 1782877500, "arr" => 1782888600 ], "layovers" => 0, "total_price" => 405, "currency" => "USD", "code" => "EK585" ]
            ]
        ];

        return collect($response['results'])
            ->filter(fn($f) => $f['route']['src'] === $from && $f['route']['dst'] === $to && Carbon::createFromTimestamp($f['times']['dep'])->isSameDay($date))
            ->map(function($f) {
                $dep = Carbon::createFromTimestamp($f['times']['dep']);
                return new FlightDTO(
                    id: FlightDTO::generateId($f['iata'], $f['code'], $dep),
                    carrier: $f['iata'],
                    flightNo: $f['code'],
                    from: $f['route']['src'],
                    to: $f['route']['dst'],
                    departureTime: $dep,
                    arrivalTime: Carbon::createFromTimestamp($f['times']['arr']),
                    stops: $f['layovers'],
                    bestPrice: (float)$f['total_price'],
                    currency: $f['currency'],
                    bestProvider: $this->getName(),
                    allOffers: [['provider' => $this->getName(), 'price' => (float)$f['total_price']]]
                );
            })->toArray();
    }
}