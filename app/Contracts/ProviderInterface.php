<?php
namespace App\Contracts;

use Carbon\Carbon;

interface ProviderInterface
{
    /**
     * @return \App\DTOs\FlightDTO[]
     */
    public function search(string $from, string $to, Carbon $date): array;
    
    public function getName(): string;
}