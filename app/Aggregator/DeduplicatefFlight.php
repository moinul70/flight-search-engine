<?php

namespace App\Aggregator;

use App\DTOs\FlightDTO;

/**
 * A flight after cross-provider deduplication.
 *
 * The "winning" offer (lowest price) is surfaced; all competing
 * provider offers are retained so callers can inspect price variance.
 */
final class DeduplicatedFlight
{
    /** @param FlightDTO[] $allOffers All offers that share the same flight fingerprint */
    public function __construct(
        public readonly FlightDTO $cheapest,
        public readonly array     $allOffers,
    ) {}

    public function priceVariance(): float
    {
        if (count($this->allOffers) <= 1) {
            return 0.0;
        }
        $prices = array_map(fn(FlightDTO $f) => $f->priceUsd, $this->allOffers);
        return round(max($prices) - min($prices), 2);
    }

    public function providerCount(): int
    {
        return count(array_unique(array_map(fn(FlightDTO $f) => $f->sourceProvider, $this->allOffers)));
    }
}