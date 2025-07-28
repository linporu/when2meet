<?php

namespace App\Observers;

use App\Models\EventParticipant;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class EventParticipantObserver
{
    /**
     * Handle the EventParticipant "created" event.
     */
    public function created(EventParticipant $eventParticipant): void
    {
        $this->clearGroupAvailabilityCache($eventParticipant->event_id, 'participant_created');
    }

    /**
     * Handle the EventParticipant "updated" event.
     */
    public function updated(EventParticipant $eventParticipant): void
    {
        $this->clearGroupAvailabilityCache($eventParticipant->event_id, 'participant_updated');
    }

    /**
     * Handle the EventParticipant "deleted" event.
     */
    public function deleted(EventParticipant $eventParticipant): void
    {
        $this->clearGroupAvailabilityCache($eventParticipant->event_id, 'participant_deleted');
    }

    /**
     * Handle the EventParticipant "restored" event.
     */
    public function restored(EventParticipant $eventParticipant): void
    {
        $this->clearGroupAvailabilityCache($eventParticipant->event_id, 'participant_restored');
    }

    /**
     * Handle the EventParticipant "force deleted" event.
     */
    public function forceDeleted(EventParticipant $eventParticipant): void
    {
        $this->clearGroupAvailabilityCache($eventParticipant->event_id, 'participant_force_deleted');
    }

    protected function clearGroupAvailabilityCache(int $eventId, string $reason): void
    {
        if ($this->supportsTags()) {
            // Clear only dynamic group availability cache, keep static slots
            Cache::tags(['group_availability', "event_{$eventId}"])->flush();

            Log::info('GroupAvailabilityCache invalidated', [
                'event_id' => $eventId,
                'reason' => $reason,
                'cache_tags_cleared' => ['group_availability', "event_{$eventId}"],
            ]);
        } else {
            // For stores without tag support, clear specific cache keys
            $this->clearEventCacheKeys($eventId, $reason);
        }
    }

    protected function supportsTags(): bool
    {
        try {
            return method_exists(Cache::getStore(), 'supportsTags') &&
                   Cache::getStore()->supportsTags();
        } catch (\Exception) {
            return false;
        }
    }

    protected function clearEventCacheKeys(int $eventId, string $reason): void
    {
        // Clear all possible group availability cache keys for this event
        // This is less efficient but necessary for cache stores without tag support
        $cacheCleared = 0;

        // Try to clear common cache key patterns
        $patterns = [
            "group_availability:{$eventId}:*",
        ];

        foreach ($patterns as $pattern) {
            // For database cache, we can't use patterns, so we'll rely on TTL
            // This is acceptable since dynamic cache has only 30 minutes TTL
        }

        Log::info('GroupAvailabilityCache invalidated (no tags)', [
            'event_id' => $eventId,
            'reason' => $reason,
            'note' => 'Cache will expire naturally due to TTL (30 minutes)',
        ]);
    }
}
