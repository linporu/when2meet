<?php

namespace App\Observers;

use App\Contracts\GroupAvailabilityServiceInterface;
use App\Models\ParticipantAvailability;
use Illuminate\Support\Facades\Log;

class ParticipantAvailabilityObserver
{
    protected GroupAvailabilityServiceInterface $groupAvailabilityService;

    public function __construct(GroupAvailabilityServiceInterface $groupAvailabilityService)
    {
        $this->groupAvailabilityService = $groupAvailabilityService;
    }

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

        // Clear all group availability cache for this event
        $this->groupAvailabilityService->clearEventCache($eventId);

        Log::info('GroupAvailabilityCache cleared', [
            'event_id' => $eventId,
            'participant_id' => $availability->participant_id,
            'reason' => $reason,
        ]);
    }
}
