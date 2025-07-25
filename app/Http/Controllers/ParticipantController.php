<?php

namespace App\Http\Controllers;

use App\Http\Requests\JoinEventRequest;
use App\Models\Event;
use App\Models\EventParticipant;
use App\Models\ParticipantAvailability;

class ParticipantController extends Controller
{
    /**
     * Handle participant name entry.
     */
    public function enterName(Event $event)
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

        return redirect()->route('events.editAvailability', [
            'event' => $event->hash,
            'participant' => $participant->id,
        ]);
    }

    /**
     * Show participant availability editing interface and handle availability updates.
     */
    public function editAvailability(Event $event, EventParticipant $participant)
    {
        // Verify participant belongs to this event
        if ($participant->event_id !== $event->id) {
            abort(404);
        }

        // Handle POST request (save availability)
        if (request()->isMethod('POST')) {
            $validatedData = $this->validateAvailabilityData();
            $availabilityData = $this->getValidatedAvailabilityData($validatedData);

            return $this->saveAvailability($availabilityData, $event, $participant);
        }

        // Handle GET request (show form)
        return $this->showEditForm($event, $participant);
    }

    /**
     * Show the edit form with current availability data.
     */
    private function showEditForm(Event $event, EventParticipant $participant)
    {
        $event->load('timeSlots');

        // Load existing availability data for this participant
        $existingAvailability = $participant->participantAvailabilities()
            ->get()
            ->groupBy('date')
            ->map(function ($availabilities) {
                return $availabilities->map(function ($availability) {
                    return [
                        'start_time' => $availability->start_time,
                        'end_time' => $availability->end_time,
                    ];
                })->toArray();
            })
            ->toArray();

        return view('participant-edit', compact('event', 'participant', 'existingAvailability'));
    }

    /**
     * Validate availability data with custom logic.
     */
    private function validateAvailabilityData(): array
    {
        $rules = [
            'participant_name' => 'required|string|max:255',
            'availability' => 'array',
            'availability.*' => 'array',
            'availability.*.*' => 'array',
            'availability.*.*.start_time' => 'nullable|date_format:H:i',
            'availability.*.*.end_time' => 'nullable|date_format:H:i',
        ];

        $messages = [
            'participant_name.required' => 'Please enter your name.',
            'participant_name.string' => 'Your name must be a valid text.',
            'participant_name.max' => 'Your name cannot exceed 255 characters.',
            'availability.*.*.start_time.date_format' => 'Start time must be in HH:MM format.',
            'availability.*.*.end_time.date_format' => 'End time must be in HH:MM format.',
        ];

        $validated = request()->validate($rules, $messages);

        // Custom validation for time ranges
        $this->validateTimeRanges($validated['availability'] ?? []);

        return $validated;
    }

    /**
     * Validate time ranges logic.
     */
    private function validateTimeRanges(array $availability): void
    {
        foreach ($availability as $date => $timeRanges) {
            foreach ($timeRanges as $index => $timeRange) {
                $startTime = $timeRange['start_time'] ?? null;
                $endTime = $timeRange['end_time'] ?? null;

                // Both start and end time must be provided if one is provided
                if (empty($startTime) || empty($endTime)) {
                    throw \Illuminate\Validation\ValidationException::withMessages([
                        "availability.{$date}.{$index}" => 'Both start time and end time must be selected.',
                    ]);
                }

                // End time must be later than start time
                if ($startTime >= $endTime) {
                    throw \Illuminate\Validation\ValidationException::withMessages([
                        "availability.{$date}.{$index}" => 'End time must be later than start time.',
                    ]);
                }
            }
        }
    }

    /**
     * Get validated availability data in flat array format for storage.
     */
    private function getValidatedAvailabilityData(array $validated): array
    {
        $availability = $validated['availability'] ?? [];
        $result = [];

        foreach ($availability as $date => $timeRanges) {
            foreach ($timeRanges as $timeRange) {
                $result[] = [
                    'date' => $date,
                    'start_time' => $timeRange['start_time'],
                    'end_time' => $timeRange['end_time'],
                ];
            }
        }

        return $result;
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
        return $this->showEditForm($event, $participant)
            ->with('success', 'Your availability has been saved successfully!');
    }

    /**
     * Handle participant joining an event with availability.
     */
    public function join(JoinEventRequest $request, Event $event)
    {
        $validated = $request->validated();

        // Create or update participant
        $participant = EventParticipant::updateOrCreate(
            [
                'event_id' => $event->id,
                'name' => $validated['participant_name'],
            ],
            [
                'event_id' => $event->id,
                'name' => $validated['participant_name'],
            ]
        );

        // Delete existing availability records for this participant
        ParticipantAvailability::where('participant_id', $participant->id)->delete();

        // Get validated availability data
        $availabilityData = $request->getValidatedAvailability();

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

        // Instead of redirecting, re-render the edit page with fresh data
        $event->load('timeSlots');

        // Load existing availability data for this participant (fresh from database)
        $existingAvailability = $participant->participantAvailabilities()
            ->get()
            ->groupBy('date')
            ->map(function ($availabilities) {
                return $availabilities->map(function ($availability) {
                    return [
                        'start_time' => $availability->start_time,
                        'end_time' => $availability->end_time,
                    ];
                })->toArray();
            })
            ->toArray();

        return view('participant-edit', compact('event', 'participant', 'existingAvailability'))
            ->with('success', 'Your availability has been saved successfully!');
    }
}
