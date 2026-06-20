<?php
namespace App\Services;

use Carbon\Carbon;
use Illuminate\Support\Collection;
use Throwable;

class FlightAggregator
{
    /** @param \App\Contracts\ProviderInterface[] $providers */
    public function __construct(private array $providers) {}

    public function search(array $criteria, ?string $sortBy = 'price', ?string $filterCarrier = null): array
    {
        $from = $criteria['from'];
        $to = $criteria['to'];
        $date = Carbon::parse($criteria['date']);

        $allFlights = [];
        $metadata = [
            'successful_providers' => [],
            'failed_providers' => []
        ];

        // Core Aggregation Loop
        foreach ($this->providers as $provider) {
            try {
                $flights = $provider->search($from, $to, $date);
                $allFlights = array_merge($allFlights, $flights);
                $metadata['successful_providers'][] = $provider->getName();
            } catch (Throwable $e) {
                // Fault tolerance: single provider failures don't crash entire lookup
                $metadata['failed_providers'][] = [
                    'provider' => $provider->getName(),
                    'error' => $e->getMessage()
                ];
            }
        }

        // Process, De-duplicate, Sort & Filter
        $processedResults = $this->processFlights(collect($allFlights), $sortBy, $filterCarrier);

        return [
            'meta' => [
                'completeness' => count($metadata['failed_providers']) === 0 ? 'COMPLETE' : 'PARTIAL',
                'providers_audited' => $metadata,
                'total_results' => $processedResults->count()
            ],
            'results' => $processedResults->values()->toArray()
        ];
    }

    private function processFlights(Collection $flights, ?string $sortBy, ?string $filterCarrier): Collection
    {
        return $flights
            ->groupBy('id') // Group identical physical flights by composite stable key
            ->map(function (Collection $groupedFlights) {
                /** @var \App\DTOs\FlightDTO $primary */
                $primary = $groupedFlights->first();
                
                $allOffers = [];
                foreach ($groupedFlights as $flight) {
                    $allOffers = array_merge($allOffers, $flight->allOffers);
                }

                // Sort offers to find the cheapest provider
                usort($allOffers, fn($a, $b) => $a['price'] <=> $b['price']);

                $primary->bestPrice = $allOffers[0]['price'];
                $primary->bestProvider = $allOffers[0]['provider'];
                $primary->allOffers = $allOffers;

                return $primary;
            })
            // Apply Filters
            ->when($filterCarrier, function($collection) use ($filterCarrier) {
                return $collection->filter(fn($f) => strcasecmp($f->carrier, $filterCarrier) === 0);
            })
            // Apply Sorting
            ->sortBy(function($flight) use ($sortBy) {
                return match($sortBy) {
                    'duration' => $flight->departureTime->diffInMinutes($flight->arrivalTime),
                    'departure' => $flight->departureTime->timestamp,
                    default => $flight->bestPrice,
                };
            });
    }
}