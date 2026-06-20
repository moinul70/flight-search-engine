# Flight Search Aggregator Engine

An enterprise-ready, fault-tolerant flight search aggregator engine built with **Laravel 13** and **MySQL**. The system concurrently structures lookups from multiple distinct third-party provider schemas, eliminates duplicate physical inventory via unique identifier compositions, and presents cleanly serialized data contracts via Laravel API Resources.

---

## Features

* Multi-provider flight search aggregation
* Provider schema normalization through DTOs
* Duplicate flight detection and price comparison
* Stable flight identifiers for downstream operations
* Booking creation and retrieval
* Fault-tolerant provider execution
* API Resource-based response serialization
* Extensible provider architecture
* Unit and Feature test coverage

---

# 1. System Architecture & Component Communication

The engine employs a **Fan-Out Aggregation Architecture** driven by a Strategy/Adapter design pattern. This ensures the application remains highly extensible, allowing developers to plug in new flight providers without altering core lookup or booking workflows.

### API Layer (Controllers & Resources)

Responsible for:

* Validating incoming requests
* Delegating business operations to services
* Returning standardized JSON responses through Laravel API Resources

### Aggregation Core (`FlightAggregator`)

Acts as the orchestration layer:

* Executes provider searches
* Handles provider failures gracefully
* Merges normalized flight data
* Deduplicates flights
* Applies filtering and sorting

### Provider Strategy Layer (`FlightProviderInterface`)

Defines a strict contract that every provider implementation must follow.

This enables:

* Easy provider replacement
* Independent provider testing
* Consistent normalization behavior

### Domain DTO (`FlightDTO`)

The internal source of truth.

Regardless of how a provider structures:

* Dates
* Prices
* Flight numbers
* Routes

all incoming data is immediately transformed into a unified DTO representation.

---

## Deduplication Strategy

Flights representing the same physical journey are grouped using:

```text
carrier + flight_number + departure_time
```

A stable Base64-encoded composite identifier is generated from this key.

Example:

```text
EK + EK585 + 2026-07-01T03:45:00
```

The engine:

* Marks the cheapest fare as `best_fare`
* Preserves all provider offers in `all_offers`
* Prevents duplicate search results from appearing to users

---

## Fault Isolation & Response Completeness

Provider failures never break the search experience.

If a provider:

* Times out
* Returns malformed data
* Throws an exception

the failure is isolated and captured.

Example metadata:

```json
{
  "status": "PARTIAL",
  "providers_requested": 3,
  "providers_responded": 2,
  "providers_failed": [
    "ProviderC"
  ]
}
```

This allows consumers to understand result completeness.

---

# 2. Directory Layout & Folder Structure

```text
flight-aggregator/
├── app/
│   ├── Contracts/
│   │   └── FlightProviderInterface.php
│   │
│   ├── DTOs/
│   │   └── FlightDTO.php
│   │
│   ├── Http/
│   │   ├── Controllers/
│   │   │   ├── BookingController.php
│   │   │   └── FlightController.php
│   │   │
│   │   └── Resources/
│   │       ├── FlightResource.php
│   │       └── BookingResource.php
│   │
│   ├── Models/
│   │   ├── Booking.php
│   │   │   │
│   └── Services/
│       ├── FlightAggregator.php
│       ├── ProviderAService.php
│       ├── ProviderBService.php
│       └── ProviderCService.php
│
├── database/
│   └── migrations/
│       ├── 2026_06_21_000000_create_bookings_table.php
│      │
├── routes/
│   └── api.php
│
├── tests/
│   ├── Feature/
│   └── Unit/
│
├── README.md
└── ARCHITECTURE.md
```

---

# 3. Installation

## Clone Repository

```bash
git clone https://github.com/moinul70/flight-search-engine.git

cd flight-search-engine
```

## Install Dependencies

```bash
composer install
```

## Environment Setup

```bash
cp .env.example .env
```

Generate application key:

```bash
php artisan key:generate
```

Configure database credentials in `.env`:

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=flight_search_engine
DB_USERNAME=root
DB_PASSWORD=
```

## Run Migrations

```bash
php artisan migrate
```

## Start Application

```bash
php artisan serve
```

Application will be available at:

```text
http://localhost:8000
```

---

# 4. API Routes

```php
Route::prefix('flights')->group(function () {
    Route::get('search', [FlightController::class, 'search']);
});

Route::prefix('bookings')->group(function () {
    Route::post('/', [BookingController::class, 'store']);
    Route::get('{reference}', [BookingController::class, 'show']);
});
```

---

# 5. Flight Search API

## Request

```http
GET /api/flights/search
```

### Query Parameters

| Parameter  | Required | Example    |
| ---------- | -------- | ---------- |
| from       | Yes      | DAC        |
| to         | Yes      | DXB        |
| date       | Yes      | 2026-07-01 |
| passengers | Yes      | 2          |
| sort       | No       | price      |
| carrier    | No       | EK         |
| stops      | No       | 0          |
| max_price  | No       | 300        |

Example:

```http
GET /api/flights/search?from=DAC&to=DXB&date=2026-07-01&passengers=2
```

---

## Sample Response

```json
{
  "meta": {
    "status": "COMPLETE",
    "providers_requested": 3,
    "providers_responded": 3,
    "providers_failed": []
  },
  "data": [
    {
      "flight_id": "RUs6RUs1ODU6MTc4Mjg3NzUwMA==",
      "carrier": "EK",
      "flight_number": "EK585",
      "from": "DAC",
      "to": "DXB",
      "departure": "2026-07-01T03:45:00Z",
      "arrival": "2026-07-01T06:50:00Z",
      "stops": 0,
      "best_fare": 399,
      "currency": "USD"
    }
  ]
}
```

---

# 6. Create Booking

## Request

```http
POST /api/bookings
```

### Payload

```json
{
  "flight_id": "RUs6RUs1ODU6MTc4Mjg3NzUwMA==",
  "passengers": [
    {
      "first_name": "John",
      "last_name": "Doe"
    }
  ]
}
```

---

## Response

```json
{
  "reference": "BK-8H5J2K",
  "flight_id": "RUs6RUs1ODU6MTc4Mjg3NzUwMA==",
  "status": "CONFIRMED"
}
```

---

# 7. Retrieve Booking

## Request

```http
GET /api/bookings/BK-8H5J2K
```

---

## Response

```json
{
  "reference": "BK-8H5J2K",
  "flight_id": "RUs6RUs1ODU6MTc4Mjg3NzUwMA==",
  "passengers": [
    {
      "first_name": "John",
      "last_name": "Doe"
    }
  ]
}
```

---

# 8. Testing

Run all tests:

```bash
php artisan test
```

Run feature tests:

```bash
php artisan test --testsuite=Feature
```

Run unit tests:

```bash
php artisan test --testsuite=Unit
```

---

# 9. Future Improvements

Potential enhancements beyond the scope of this exercise:

* Parallel provider execution using Laravel HTTP Pool
* Redis caching for repeated searches
* Provider health monitoring
* Circuit breaker implementation
* Search result pagination
* OpenAPI / Swagger documentation
* Distributed tracing and observability
* Booking snapshot persistence
* Queue-based provider synchronization

---

# License

This project is provided as a technical assessment implementation and demonstration of scalable backend architecture patterns using Laravel.
