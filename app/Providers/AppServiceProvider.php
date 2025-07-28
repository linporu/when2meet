<?php

namespace App\Providers;

use App\Contracts\GroupAvailabilityServiceInterface;
use App\Models\EventParticipant;
use App\Models\ParticipantAvailability;
use App\Observers\EventParticipantObserver;
use App\Observers\ParticipantAvailabilityObserver;
use App\Services\CachedGroupAvailabilityService;
use App\Services\GroupAvailabilityService;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Register GroupAvailabilityService with caching
        $this->app->bind(GroupAvailabilityServiceInterface::class, function ($app) {
            $baseService = new GroupAvailabilityService;

            // Enable caching by default, can be disabled via config
            if (config('cache.enable_group_availability_cache', true)) {
                return new CachedGroupAvailabilityService(
                    $baseService,
                    $app['cache.store']
                );
            }

            return $baseService;
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Register model observers for cache invalidation
        EventParticipant::observe(EventParticipantObserver::class);
        ParticipantAvailability::observe(ParticipantAvailabilityObserver::class);
    }
}
