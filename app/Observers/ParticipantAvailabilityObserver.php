<?php

namespace App\Observers;

use App\Models\ParticipantAvailability;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class ParticipantAvailabilityObserver
{
    /**
     * Handle the ParticipantAvailability "created" event.
     */
    public function created(ParticipantAvailability $participantAvailability): void
    {
        $this->clearGroupAvailabilityCache($participantAvailability, 'availability_created');
    }

    /**
     * Handle the ParticipantAvailability "updated" event.
     */
    public function updated(ParticipantAvailability $participantAvailability): void
    {
        $this->clearGroupAvailabilityCache($participantAvailability, 'availability_updated');
    }

    /**
     * Handle the ParticipantAvailability "deleted" event.
     */
    public function deleted(ParticipantAvailability $participantAvailability): void
    {
        $this->clearGroupAvailabilityCache($participantAvailability, 'availability_deleted');
    }

    /**
     * Handle the ParticipantAvailability "restored" event.
     */
    public function restored(ParticipantAvailability $participantAvailability): void
    {
        $this->clearGroupAvailabilityCache($participantAvailability, 'availability_restored');
    }

    /**
     * Handle the ParticipantAvailability "force deleted" event.
     */
    public function forceDeleted(ParticipantAvailability $participantAvailability): void
    {
        $this->clearGroupAvailabilityCache($participantAvailability, 'availability_force_deleted');
    }

    protected function clearGroupAvailabilityCache(ParticipantAvailability $availability, string $reason): void
    {
        $eventId = $availability->participant->event_id;

        if ($this->supportsTags()) {
            // Clear only dynamic group availability cache, keep static slots
            Cache::tags(['group_availability', "event_{$eventId}"])->flush();

            Log::info('GroupAvailabilityCache invalidated', [
                'event_id' => $eventId,
                'participant_id' => $availability->participant_id,
                'reason' => $reason,
                'cache_tags_cleared' => ['group_availability', "event_{$eventId}"],
            ]);
        } else {
            // For stores without tag support, rely on TTL
            Log::info('GroupAvailabilityCache invalidated (no tags)', [
                'event_id' => $eventId,
                'participant_id' => $availability->participant_id,
                'reason' => $reason,
                'note' => 'Cache will expire naturally due to TTL (30 minutes)',
            ]);
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
}
