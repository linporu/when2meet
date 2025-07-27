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
        $event->load('timeSlots', 'participants.participantAvailabilities');

        // Calculate group availability for visualization
        $groupAvailability = $this->calculateGroupAvailability($event);

        return view('event-show', compact('event', 'groupAvailability'));
    }

    /**
     * Calculate group availability for all participants in 30-minute slots.
     */
    private function calculateGroupAvailability(Event $event): array
    {
        $groupAvailability = [];
        $totalParticipants = $event->participants->count();

        // Generate all possible 30-minute time slots for each event date
        foreach ($event->timeSlots as $timeSlot) {
            $dateKey = $timeSlot->date->format('Y-m-d');
            $timeSlots = $this->generateTimeSlots($timeSlot->start_time, $timeSlot->end_time);

            foreach ($timeSlots as $slot) {
                // Count participants available for this specific time slot
                $availableParticipants = $this->getAvailableParticipants(
                    $event,
                    $dateKey,
                    $slot['start_time'],
                    $slot['end_time']
                );

                $groupAvailability[] = [
                    'date' => $dateKey,
                    'start_time' => $slot['start_time'],
                    'end_time' => $slot['end_time'],
                    'available_count' => count($availableParticipants),
                    'total_participants' => $totalParticipants,
                    'available_participants' => $availableParticipants,
                    'availability_percentage' => $totalParticipants > 0
                        ? round((count($availableParticipants) / $totalParticipants) * 100)
                        : 0,
                ];
            }
        }

        // Sort by date and time
        usort($groupAvailability, function ($a, $b) {
            $dateComparison = strcmp($a['date'], $b['date']);
            if ($dateComparison === 0) {
                return strcmp($a['start_time'], $b['start_time']);
            }

            return $dateComparison;
        });

        return $groupAvailability;
    }

    /**
     * Generate 30-minute time slots between start and end time.
     */
    private function generateTimeSlots(string $startTime, string $endTime): array
    {
        $slots = [];
        $current = Carbon::createFromFormat('H:i:s', $startTime);
        $end = Carbon::createFromFormat('H:i:s', $endTime);
        $interval = 30; // 30 minutes

        while ($current->lt($end)) {
            $slotStart = $current->format('H:i');
            $slotEnd = $current->copy()->addMinutes($interval)->format('H:i');

            // Don't add slot if it goes beyond the end time
            if ($current->copy()->addMinutes($interval)->lte($end)) {
                $slots[] = [
                    'start_time' => $slotStart,
                    'end_time' => $slotEnd,
                ];
            }

            $current->addMinutes($interval);
        }

        return $slots;
    }

    /**
     * Get list of participants available for a specific time slot.
     */
    private function getAvailableParticipants(Event $event, string $date, string $startTime, string $endTime): array
    {
        $availableParticipants = [];

        foreach ($event->participants as $participant) {
            foreach ($participant->participantAvailabilities as $availability) {
                // Check if this availability record matches the date and overlaps with the time slot
                if ($availability->date->format('Y-m-d') === $date
                    && $this->timeSlotOverlaps(
                        $availability->start_time,
                        $availability->end_time,
                        $startTime,
                        $endTime
                    )) {
                    $availableParticipants[] = $participant->name;
                    break; // Participant is available, no need to check other availability records
                }
            }
        }

        return array_unique($availableParticipants);
    }

    /**
     * Check if two time ranges overlap.
     */
    private function timeSlotOverlaps(string $availStart, string $availEnd, string $slotStart, string $slotEnd): bool
    {
        // Handle both H:i and H:i:s formats for availability times
        $availStartTime = $this->parseTimeString($availStart);
        $availEndTime = $this->parseTimeString($availEnd);
        $slotStartTime = Carbon::createFromFormat('H:i', $slotStart);
        $slotEndTime = Carbon::createFromFormat('H:i', $slotEnd);

        // Check if the availability time range covers the entire slot
        return $availStartTime->lte($slotStartTime) && $availEndTime->gte($slotEndTime);
    }

    /**
     * Parse time string handling both H:i and H:i:s formats.
     */
    private function parseTimeString(string $timeString): Carbon
    {
        // Try H:i:s format first, then fall back to H:i
        try {
            return Carbon::createFromFormat('H:i:s', $timeString);
        } catch (\Exception $e) {
            return Carbon::createFromFormat('H:i', $timeString);
        }
    }
}
