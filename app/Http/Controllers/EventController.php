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

        return redirect()
            ->route('events.show', $event->hash)
            ->with('success', 'Successfully joined the event and saved your availability!');
    }
}
