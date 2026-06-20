<?php
namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;

class FlightCollection extends ResourceCollection
{
    private array $metaData;

    public function __construct($resource, array $metaData)
    {
        parent::__construct($resource);
        $this->metaData = $metaData;
    }

    public function toArray(Request $request): array
    {
        return [
            'meta' => [
                'status' => $this->metaData['completeness'],
                'providers_audited' => $this->metaData['providers_audited'],
                'total_results' => $this->collection->count(),
            ],
            'data' => $this->collection,
        ];
    }
}