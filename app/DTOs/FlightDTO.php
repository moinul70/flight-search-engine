<?php
namespace App\DTOs;
use Carbon\Carbon;
 
/**
 * Canonical, provider-agnostic flight representation.
 *
 * All provider adapters normalize into this shape before any
 * aggregation logic runs. Nothing outside the Aggregator layer
 * should ever know which provider a flight came from.
 */
final class FlightDTO
{
    public function __construct(
        public string $id, // Stable composite identifier: Base64(carrier|flight_no|timestamp)
        public string $carrier,
        public string $flightNo,
        public string $from,
        public string $to,
        public Carbon $departureTime,
        public Carbon $arrivalTime,
        public int $stops,
        public float $bestPrice,
        public string $currency,
        public string $bestProvider,
        public array $allOffers = [] // Contains all provider variations [{provider, price}]
    ) {}

    public static function generateId(string $carrier, string $flightNo, Carbon $departureTime): string
    {
        return base64_encode("{$carrier}|{$flightNo}|" . $departureTime->toIso8601String());
    }
}