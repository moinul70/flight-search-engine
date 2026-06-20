<?php

namespace App\Http\Controllers;

use App\Http\Resources\FlightCollection;
use App\Http\Resources\FlightResource;
use App\Services\FlightAggregator;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class FlightController extends Controller
{
    public function __construct(private FlightAggregator $aggregator) {}

    public function search(Request $request)
    {
        $validated = $request->validate([
            'from'       => ['required', 'string', 'size:3', 'regex:/^[A-Z]{3}$/i'],
            'to'         => ['required', 'string', 'size:3', 'regex:/^[A-Z]{3}$/i'],
            'date'       => ['required', 'date_format:Y-m-d', 'after_or_equal:today'],
            'passengers' => ['sometimes', 'integer', 'min:1', 'max:9'],
            'sort_by' => 'nullable|string|in:price,duration,departure',
            'carrier' => 'nullable|string'
        ]);

        $payload = $this->aggregator->search(
            criteria: $validated,
            sortBy: $request->query('sort_by', 'price'),
            filterCarrier: $request->query('carrier')
        );

        // Map the payload into our explicit resource structures
        return new FlightCollection($payload['results'], $payload['meta']);
    }
}
