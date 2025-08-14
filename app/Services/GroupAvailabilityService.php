<?php

namespace App\Services;

use App\Contracts\GroupAvailabilityServiceInterface;
use App\Models\Event;
use Carbon\Carbon;

class GroupAvailabilityService implements GroupAvailabilityServiceInterface
{
    /**
     * Calculate group availability for all participants in 30-minute slots.
     * Returns all event time slots with participant availability data.
     */
    public function calculateGroupAvailability(Event $event): array
    {
        $rawData = $this->getEventParticipantAvailabilityData($event);

        // Calculate total participants once
        $totalParticipants = collect($rawData)
            ->pluck('participant_id')
            ->filter()
            ->unique()
            ->count();

        // Generate all possible time slots from event definition
        $allTimeSlots = collect($rawData)
            ->map(fn ($row) => [
                'date' => $row['date'],
                'start_time' => $row['slot_start_time'],
                'end_time' => $row['slot_end_time'],
            ])
            ->unique()
            ->flatMap(fn ($slot) => $this->generateEventTimeSlots($slot))
            ->keyBy('key');

        // Get participant availability slots
        $participantSlots = collect($rawData)
            ->filter(fn ($row) => ! empty($row['participant_name']) && ! empty($row['avail_start_time']))
            ->flatMap(fn ($row) => $this->generateParticipantSlots($row))
            ->groupBy('key');

        // Merge all time slots with participant data
        return $allTimeSlots
            ->map(fn ($slot) => [
                'date' => $slot['date'],
                'start_time' => $slot['start_time'],
                'end_time' => $slot['end_time'],
                'available_participants' => $participantSlots->get($slot['key'], collect())->pluck('participant')->unique()->values()->toArray(),
                'available_count' => $participantSlots->get($slot['key'], collect())->pluck('participant')->unique()->count(),
                'total_participants' => $totalParticipants,
                'availability_percentage' => $totalParticipants > 0
                    ? round(($participantSlots->get($slot['key'], collect())->pluck('participant')->unique()->count() / $totalParticipants) * 100)
                    : 0,
            ])
            ->sortKeys()
            ->values()
            ->toArray();
    }

    /**
     * Get optimized availability data for group availability calculation.
     * Uses Eloquent Builder with JOIN to avoid N+1 issues while staying ORM-friendly.
     */
    private function getEventParticipantAvailabilityData(Event $event): array
    {
        return $event->timeSlots()
            ->leftJoin('event_participants as ep', 'ep.event_id', '=', 'event_time_slots.event_id')
            ->leftJoin('participant_availabilities as pa', function ($join) {
                $join->on('pa.participant_id', '=', 'ep.id')
                    ->on('pa.date', '=', 'event_time_slots.date');
            })
            ->select([
                'event_time_slots.date',
                'event_time_slots.start_time as slot_start_time',
                'event_time_slots.end_time as slot_end_time',
                'ep.id as participant_id',
                'ep.name as participant_name',
                'pa.start_time as avail_start_time',
                'pa.end_time as avail_end_time',
            ])
            ->orderBy('event_time_slots.date')
            ->orderBy('event_time_slots.start_time')
            ->get()
            ->toArray();
    }

    /**
     * Generate participant slot data with unique keys for a single availability record.
     */
    private function generateParticipantSlots(array $availabilityRow): array
    {
        if (empty($availabilityRow['avail_start_time']) || empty($availabilityRow['avail_end_time'])) {
            return [];
        }

        $date = $availabilityRow['date'];
        $participant = $availabilityRow['participant_name'];
        $availStart = $this->parseTimeString($availabilityRow['avail_start_time']);
        $availEnd = $this->parseTimeString($availabilityRow['avail_end_time']);

        $slots = [];
        $current = $availStart->copy();
        $interval = 30;

        while ($current->lt($availEnd)) {
            $slotStart = $current->format('H:i');
            $slotEnd = $current->copy()->addMinutes($interval)->format('H:i');

            if ($current->copy()->addMinutes($interval)->lte($availEnd)) {
                $slotKey = $date.'_'.$slotStart.'_'.$slotEnd;
                $slots[] = [
                    'key' => $slotKey,
                    'date' => $date,
                    'start_time' => $slotStart,
                    'end_time' => $slotEnd,
                    'participant' => $participant,
                ];
            }

            $current->addMinutes($interval);
        }

        return $slots;
    }

    /**
     * Generate all 30-minute slots for a single event time slot definition.
     */
    private function generateEventTimeSlots(array $timeSlot): array
    {
        $date = $timeSlot['date'];
        $start = $this->parseTimeString($timeSlot['start_time']);
        $end = $this->parseTimeString($timeSlot['end_time']);

        $slots = [];
        $current = $start->copy();
        $interval = 30;

        while ($current->lt($end)) {
            $slotStart = $current->format('H:i');
            $slotEnd = $current->copy()->addMinutes($interval)->format('H:i');

            if ($current->copy()->addMinutes($interval)->lte($end)) {
                $slotKey = $date.'_'.$slotStart.'_'.$slotEnd;
                $slots[] = [
                    'key' => $slotKey,
                    'date' => $date,
                    'start_time' => $slotStart,
                    'end_time' => $slotEnd,
                ];
            }

            $current->addMinutes($interval);
        }

        return $slots;
    }

    /**
     * Parse time string handling both H:i and H:i:s formats.
     */
    private function parseTimeString(string $timeString): Carbon
    {
        // Try H:i:s format first, then fall back to H:i
        try {
            return Carbon::createFromFormat('H:i:s', $timeString);
        } catch (\Exception) {
            return Carbon::createFromFormat('H:i', $timeString);
        }
    }

    public function getCacheStats(int $eventId): array
    {
        // Base service doesn't support caching
        return [];
    }

    public function clearEventCache(int $eventId): void
    {
        // Base service doesn't support caching - no-op
    }
}
