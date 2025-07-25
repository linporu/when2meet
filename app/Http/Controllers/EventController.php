<?php

namespace App\Http\Controllers;

use App\Http\Requests\JoinEventRequest;
use App\Http\Requests\StoreEventRequest;
use App\Models\Event;
use App\Models\EventParticipant;
use App\Models\EventTimeSlot;
use App\Models\ParticipantAvailability;
use Carbon\Carbon;

class EventController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return view('create-event');
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('create-event');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreEventRequest $request)
    {
        $validated = $request->validated();

        $event = Event::create([
            'name' => $validated['event_name'],
        ]);

        $startDateTime = Carbon::createFromFormat(
            'Y-m-d H:i',
            $validated['date'].' '.$validated['start_time'],
            $validated['timezone']
        );

        $endDateTime = Carbon::createFromFormat(
            'Y-m-d H:i',
            $validated['date'].' '.$validated['end_time'],
            $validated['timezone']
        );

        EventTimeSlot::create([
            'event_id' => $event->id,
            'date' => $validated['date'],
            'start_time' => $startDateTime->utc()->format('H:i:s'),
            'end_time' => $endDateTime->utc()->format('H:i:s'),
        ]);

        return redirect('/'.$event->hash);
    }

    /**
     * Display the specified resource.
     */
    public function show(Event $event)
    {
        $event->load('timeSlots');

        return view('event-show', compact('event'));
    }

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
            $joinRequest = JoinEventRequest::createFromBase(request());
            $joinRequest->validateResolved();

            return $this->saveAvailability($joinRequest, $event, $participant);
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
     * Save participant availability and re-render the form.
     */
    private function saveAvailability(JoinEventRequest $request, Event $event, EventParticipant $participant)
    {

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
