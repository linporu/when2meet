<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdateAvailabilityRequest;
use App\Models\Event;
use App\Models\EventParticipant;
use App\Models\ParticipantAvailability;
use App\Services\GroupAvailabilityService;
use Carbon\Carbon;

class ParticipantController extends Controller
{
    public function __construct(
        private GroupAvailabilityService $groupAvailabilityService
    ) {
    }

    /**
     * Set participant name and redirect to availability editing.
     */
    public function setName(Event $event)
    {
        $validated = request()->validate([
            'participant_name' => 'required|string|max:255',
        ]);

        // Create or find participant
        $participant = EventParticipant::firstOrCreate(
            [
                'event_id' => $event->id,
                'name' => $validated['participant_name'],
            ],
            [
                'event_id' => $event->id,
                'name' => $validated['participant_name'],
            ]
        );

        return redirect()->route('participants.availability.edit', [
            'event' => $event->hash,
            'participant' => $participant->id,
        ]);
    }

    /**
     * Show participant availability editing interface.
     */
    public function show(Event $event, EventParticipant $participant)
    {
        // Verify participant belongs to this event
        if ($participant->event_id !== $event->id) {
            return redirect()->route('events.show', $event->hash)
                ->with('error', 'Invalid participant access. Please enter your name to continue.');
        }

        // Prepare edit form data
        $formData = $this->prepareEditFormData($event, $participant);

        // Calculate group availability for visualization
        $groupAvailability = $this->groupAvailabilityService->calculateGroupAvailability($event);

        return view('participant-edit', [
            'event' => $event,
            'participant' => $participant,
            'existingAvailability' => $formData['existingAvailability'],
            'timeOptions' => $formData['timeOptions'],
            'groupAvailability' => $groupAvailability,
        ]);
    }

    /**
     * Prepare edit form data for participant availability editing.
     */
    private function prepareEditFormData(Event $event, EventParticipant $participant): array
    {
        $event->load('timeSlots');

        // Load existing availability data for this participant
        $existingAvailability = $participant->participantAvailabilities()
            ->get()
            ->groupBy(function ($availability) {
                return $availability->date->format('Y-m-d');
            })
            ->map(function ($availabilities) {
                return $availabilities->map(function ($availability) {
                    return [
                        'start_time' => $availability->start_time,
                        'end_time' => $availability->end_time,
                    ];
                })->toArray();
            })
            ->toArray();

        // Generate time options for each time slot
        $timeOptions = [];
        foreach ($event->timeSlots as $timeSlot) {
            $dateKey = $timeSlot->date->format('Y-m-d');
            $timeOptions[$dateKey] = $this->generateTimeOptions(
                $timeSlot->start_time,
                $timeSlot->end_time
            );
        }

        return [
            'existingAvailability' => $existingAvailability,
            'timeOptions' => $timeOptions,
        ];
    }

    /**
     * Generate time options for select dropdowns.
     */
    private function generateTimeOptions(string $startTime, string $endTime): array
    {
        $options = [];
        $current = Carbon::createFromFormat('H:i:s', $startTime);
        $end = Carbon::createFromFormat('H:i:s', $endTime);
        $interval = 30; // 30 minutes

        while ($current <= $end) {
            $timeValue = $current->format('H:i');
            $timeDisplay = $current->format('g:i A');
            $options[$timeValue] = $timeDisplay;
            $current->addMinutes($interval);
        }

        return $options;
    }

    /**
     * Update participant availability.
     */
    public function update(UpdateAvailabilityRequest $request, Event $event, EventParticipant $participant)
    {
        $availabilityData = $request->getFormattedAvailability();

        return $this->saveAvailability($availabilityData, $event, $participant);
    }

    /**
     * Save participant availability and re-render the form.
     */
    private function saveAvailability(array $availabilityData, Event $event, EventParticipant $participant)
    {
        // Delete existing availability records for this participant
        ParticipantAvailability::where('participant_id', $participant->id)->delete();

        // Create new availability records
        foreach ($availabilityData as $availability) {
            ParticipantAvailability::create([
                'event_id' => $event->id,
                'participant_id' => $participant->id,
                'date' => $availability['date'],
                'start_time' => $availability['start_time'],
                'end_time' => $availability['end_time'],
            ]);
        }

        // Re-render the form with fresh data and success message
        return $this->show($event, $participant)
            ->with('success', 'Your availability has been saved successfully!');
    }
}
