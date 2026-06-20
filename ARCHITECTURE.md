# ARCHITECTURE.md

# Flight Search Engine Architecture

## Overview

The Flight Search Engine is designed as a lightweight, extensible backend service that aggregates flight inventory from multiple providers, normalizes disparate schemas into a unified model, removes duplicate flight entries, and exposes a consistent API for flight search and booking operations.

The architecture prioritizes:

* Separation of concerns
* Extensibility
* Fault tolerance
* Testability
* API consistency

---

# High-Level Flow

```text
Client
  │
  ▼
FlightController
  │
  ▼
FlightAggregator
  │
  ├── ProviderAService
  ├── ProviderBService
  └── ProviderCService
  │
  ▼
FlightDTO Normalization
  │
  ▼
Deduplication Engine
  │
  ▼
Filtering & Sorting
  │
  ▼
FlightResource
  │
  ▼
JSON Response
```

For bookings:

```text
Client
  │
  ▼
BookingController
  │
  ▼
Booking Service
  │
  ▼
Booking Model
  │
  ▼
Passenger Model
  │
  ▼
BookingResource
  │
  ▼
JSON Response
```

---

# Core Components

## Controllers

Controllers remain intentionally thin.

Responsibilities:

* Request validation
* Delegating business logic
* Returning API Resources

Controllers never contain:

* Provider logic
* Deduplication logic
* Persistence logic

Example:

```php
public function search(SearchFlightRequest $request)
{
    return FlightResource::collection(
        $this->aggregator->search($request->validated())
    );
}
```

---

# FlightAggregator

The `FlightAggregator` acts as the orchestration layer.

Responsibilities:

1. Execute provider searches
2. Collect provider responses
3. Normalize provider payloads
4. Deduplicate identical flights
5. Apply filters
6. Apply sorting
7. Build metadata envelope

Example flow:

```text
Provider A
          \
Provider B ----> Aggregator ----> Unified Response
          /
Provider C
```

This prevents controllers from becoming aware of provider-specific implementations.

---

# Provider Strategy Pattern

Every provider implements the same contract:

```php
interface FlightProviderInterface
{
    public function search(
        string $from,
        string $to,
        string $date,
        int $passengers
    ): array;
}
```

Benefits:

* New providers require zero changes to aggregation logic
* Easy mocking during testing
* Consistent normalization process

Current implementations:

```text
ProviderAService
ProviderBService
ProviderCService
```

Future additions:

```text
ProviderDService
AmadeusProviderService
SabreProviderService
SkyscannerProviderService
```

can be added without modifying existing code.

---

# FlightDTO

Providers return completely different payload structures.

To avoid leaking external schemas throughout the application, every response is immediately converted into a common DTO.

```php
class FlightDTO
{
    public string $flightId;
    public string $carrier;
    public string $flightNumber;
    public string $from;
    public string $to;
    public Carbon $departureTime;
    public Carbon $arrivalTime;
    public int $stops;
    public float $price;
    public string $currency;
}
```

The DTO becomes the application's internal source of truth.

---

# Deduplication Engine

## Problem

Multiple providers may return the same physical flight with different prices.

Example:

| Provider | Flight | Price |
| -------- | ------ | ----- |
| A        | EK585  | 410   |
| B        | EK585  | 399   |
| C        | EK585  | 405   |

Showing all entries would create a poor user experience.

---

## Solution

Flights are grouped using:

```text
carrier + flight_number + departure_time
```

Example:

```text
EK + EK585 + 2026-07-01T03:45:00
```

This composite key uniquely identifies a physical flight regardless of provider.

---

## Best Fare Selection

After grouping:

```text
EK585
 ├─ Provider A → $410
 ├─ Provider B → $399
 └─ Provider C → $405
```

The aggregator selects:

```text
Best Fare = $399
```

while preserving all available offers.

Example:

```json
{
  "best_fare": 399,
  "all_offers": [
    {
      "provider": "ProviderA",
      "price": 410
    },
    {
      "provider": "ProviderB",
      "price": 399
    }
  ]
}
```

---

# Stable Flight Identifier

A stable identifier is required for downstream operations such as booking.

The identifier is generated from:

```text
carrier
flight_number
departure_timestamp
```

Example:

```text
EK:EK585:1782877500
```

Encoded as:

```text
RUs6RUs1ODU6MTc4Mjg3NzUwMA==
```

Advantages:

* Deterministic
* Provider-independent
* Safe to expose publicly
* Consistent across searches

---

# Fault Tolerance

Provider failures must not break the search experience.

Each provider call is wrapped in exception handling:

```php
try {
    // Provider search
} catch (\Throwable $exception) {
    // Capture failure
}
```

Failures are recorded inside response metadata.

Example:

```json
{
  "status": "PARTIAL",
  "providers_failed": [
    "ProviderC"
  ]
}
```

This allows consumers to understand result completeness.

---

# API Resource Layer

Laravel API Resources provide a dedicated presentation layer.

Benefits:

* Consistent response structure
* Easy versioning
* Separation between internal DTOs and external contracts

Example:

```php
class FlightResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'flight_id' => $this->flightId,
            'carrier' => $this->carrier,
            'price' => $this->price,
        ];
    }
}
```

---

# Booking Architecture

Bookings are intentionally separated from search operations.

## Booking Model

Stores:

```text
reference
flight_id
status
```

## Passenger Model

Stores:

```text
booking_id
first_name
last_name
```

Relationship:

```text
Booking
   │
   └── hasMany
          │
          ▼
      Passenger
```

This design supports:

* Multiple passengers per booking
* Future booking modifications
* Additional traveler metadata

---

# Database Design

## bookings

| Column     | Type      |
| ---------- | --------- |
| id         | bigint    |
| reference  | string    |
| flight_id  | string    |
| status     | string    |
| created_at | timestamp |

Indexes:

```sql
UNIQUE(reference)
INDEX(flight_id)
```

---

## passengers

| Column     | Type   |
| ---------- | ------ |
| id         | bigint |
| booking_id | bigint |
| first_name | string |
| last_name  | string |

Indexes:

```sql
INDEX(booking_id)
```

---

# Testing Strategy

## Unit Tests

Focus on isolated components.

Examples:

### Provider Mapping

```text
ProviderAServiceTest
ProviderBServiceTest
ProviderCServiceTest
```

Validate normalization into DTOs.

---

### Deduplication

```text
FlightAggregatorTest
```

Validates:

* Duplicate grouping
* Cheapest fare selection
* Stable ID generation

---

## Feature Tests

Validate complete API behavior.

Examples:

```text
GET /api/flights/search

POST /api/bookings

GET /api/bookings/{reference}
```

Assertions include:

* HTTP status codes
* Response schemas
* Booking persistence

---

# Scalability Considerations

The current implementation is intentionally simple for the assessment while remaining production-friendly.

Future enhancements include:

## Parallel Provider Execution

Use Laravel HTTP Pool:

```php
Http::pool(...)
```

to reduce search latency.

---

## Redis Search Cache

Cache identical searches for a short TTL.

Benefits:

* Lower provider load
* Faster response times

---

## Circuit Breaker

Temporarily disable unhealthy providers.

Benefits:

* Improved resilience
* Reduced timeout impact

---

## OpenAPI Documentation

Generate machine-readable API specifications.

---

## Search Pagination

Support large result sets without excessive payload sizes.

---

## Event-Driven Booking Workflow

Future booking events:

```text
BookingCreated
BookingCancelled
BookingConfirmed
```

can be published to queues or external systems.

---

# Design Principles

The architecture follows several guiding principles:

1. Single Responsibility Principle
2. Dependency Inversion Principle
3. Provider-Agnostic Core Logic
4. Explicit API Contracts
5. Fail Gracefully
6. Keep Controllers Thin
7. Favor Composition Over Conditionals

The result is a maintainable, extensible, and fault-tolerant flight aggregation engine that can easily evolve as additional providers and booking requirements are introduced.
