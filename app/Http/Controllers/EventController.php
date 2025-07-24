<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreEventRequest;
use App\Models\Event;
use App\Models\EventTimeSlot;
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
        $event->load('eventDatetimes');

        return view('event-show', compact('event'));
    }
}
