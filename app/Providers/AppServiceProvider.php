<?php

namespace App\Providers;

use App\Services\FlightAggregator;
use App\Services\ProviderAService;
use App\Services\ProviderBService;
use App\Services\ProviderCService;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(FlightAggregator::class, function ($app) {
            return new FlightAggregator([
                $app->make(ProviderAService::class),
                $app->make(ProviderBService::class),
                $app->make(ProviderCService::class),
            ]);
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
