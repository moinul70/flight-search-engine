<?php
namespace Tests\Feature;

use Tests\TestCase;

class FlightSearchTest extends TestCase
{
    public function test_flight_search_returns_consolidated_and_deduplicated_results()
    {
        $response = $this->getJson('/api/flights/search?from=DAC&to=DXB&date=2026-07-01&passengers=2');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'meta' => ['completeness', 'providers_audited', 'total_results'],
                'results' => [
                    '*' => ['id', 'carrier', 'flightNo', 'bestPrice', 'allOffers']
                ]
            ]);

        // Validate that EK585 was deduplicated down to single entity containing 3 alternate offers
        $data = $response->json('results');
        $ek585 = collect($data)->firstWhere('flightNo', 'EK585');

        $this->assertNotNull($ek585);
        $this->assertEquals(399.00, $ek585['bestPrice']); // Verification: Provider B offers cheapest price for EK585
        $this->assertCount(3, $ek585['allOffers']); // Combined elements found across A, B, and C
    }
}