<?php

namespace App\Observers;

use App\Contracts\GroupAvailabilityServiceInterface;
use App\Models\EventParticipant;
use Illuminate\Support\Facades\Log;

class EventParticipantObserver
{
    protected GroupAvailabilityServiceInterface $groupAvailabilityService;

    public function __construct(GroupAvailabilityServiceInterface $groupAvailabilityService)
    {
        $this->groupAvailabilityService = $groupAvailabilityService;
    }

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
        // Clear all group availability cache for this event
        $this->groupAvailabilityService->clearEventCache($eventId);

        Log::info('GroupAvailabilityCache cleared', [
            'event_id' => $eventId,
            'reason' => $reason,
        ]);
    }
}
