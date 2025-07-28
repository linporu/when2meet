<?php

namespace App\Contracts;

use App\Models\Event;

interface GroupAvailabilityServiceInterface
{
    /**
     * Calculate group availability for all participants in 30-minute slots.
     * Returns all event time slots with participant availability data.
     */
    public function calculateGroupAvailability(Event $event): array;

    /**
     * Get cache statistics for a specific event.
     * Returns empty array if caching is not supported.
     */
    public function getCacheStats(int $eventId): array;

    /**
     * Clear cache for a specific event.
     * No-op if caching is not supported.
     */
    public function clearEventCache(int $eventId): void;
}
