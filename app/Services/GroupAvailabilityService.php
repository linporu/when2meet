<?php

namespace App\Services;

use App\Models\Event;
use Carbon\Carbon;

class GroupAvailabilityService
{
    /**
     * Calculate group availability for all participants in 30-minute slots.
     * Optimized version with matrix transpose and clean algorithm structure.
     */
    public function calculateGroupAvailability(Event $event): array
    {
        // Step 1: Load all data in a single optimized query
        $rawData = $event->getGroupAvailabilityData();

        // Step 2: Pre-process all event time slots and generate sorted time slots
        $allTimeSlots = $this->preprocessTimeSlots($rawData);

        // Step 3: Build matrix transpose index for O(1) participant lookups
        $timeSlotToParticipants = $this->buildIndexes($rawData);

        // Step 4: Calculate total participants from raw data
        $totalParticipants = collect($rawData)
            ->pluck('participant_id')
            ->filter()
            ->unique()
            ->count();

        // Step 5: Generate group availability by iterating pre-processed slots
        $groupAvailability = [];
        foreach ($allTimeSlots as $timeSlot) {
            // O(1) lookup from matrix transpose index
            $slotKey = $timeSlot['date'].'_'.$timeSlot['start_time'].'_'.$timeSlot['end_time'];
            $availableParticipants = $timeSlotToParticipants[$slotKey] ?? [];

            $groupAvailability[] = [
                'date' => $timeSlot['date'],
                'start_time' => $timeSlot['start_time'],
                'end_time' => $timeSlot['end_time'],
                'available_count' => count($availableParticipants),
                'total_participants' => $totalParticipants,
                'available_participants' => $availableParticipants,
                'availability_percentage' => $totalParticipants > 0
                    ? round((count($availableParticipants) / $totalParticipants) * 100)
                    : 0,
            ];
        }

        return $groupAvailability;
    }

    /**
     * Pre-process all event time slots and generate sorted 30-minute slots.
     */
    private function preprocessTimeSlots(array $rawData): array
    {
        $allTimeSlots = [];

        // Extract unique time slot definitions from raw data
        $uniqueTimeSlots = collect($rawData)
            ->map(fn ($row) => [
                'date' => $row['date'],
                'start_time' => $row['slot_start_time'],
                'end_time' => $row['slot_end_time'],
            ])
            ->unique()
            ->toArray();

        // Generate all 30-minute slots for each time slot definition
        foreach ($uniqueTimeSlots as $timeSlotData) {
            $slots = $this->generateTimeSlots(
                $timeSlotData['start_time'],
                $timeSlotData['end_time']
            );

            foreach ($slots as $slot) {
                $allTimeSlots[] = [
                    'date' => $timeSlotData['date'],
                    'start_time' => $slot['start_time'],
                    'end_time' => $slot['end_time'],
                ];
            }
        }

        // Sort by date and time
        usort($allTimeSlots, function ($a, $b) {
            $dateComparison = strcmp($a['date'], $b['date']);
            if ($dateComparison === 0) {
                return strcmp($a['start_time'], $b['start_time']);
            }

            return $dateComparison;
        });

        return $allTimeSlots;
    }

    /**
     * Build matrix transpose index: timeSlot->participants for O(1) lookups.
     * Returns only the essential index needed for calculations.
     */
    private function buildIndexes(array $rawData): array
    {
        // Matrix transpose: participant->timeSlots to timeSlot->participants
        // Using Laravel Collection mapToGroups for efficient transpose
        return collect($rawData)
            ->filter(fn ($row) => ! empty($row['participant_name']) && ! empty($row['avail_start_time']))
            ->mapToGroups(function ($row) {
                $row = (array) $row;
                $slots = $this->generateSlotsFromAvailability($row);

                $result = [];
                foreach ($slots as $slot) {
                    $result[$slot['key']] = $row['participant_name'];
                }

                return $result;
            })
            ->map(fn ($group) => $group->unique()->values()->toArray())
            ->toArray();
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

    /**
     * Generate all 30-minute slot keys that are covered by a single availability record.
     * Used for matrix transpose optimization.
     */
    private function generateSlotsFromAvailability(array $availabilityRow): array
    {
        // Skip if no availability data
        if (empty($availabilityRow['avail_start_time']) || empty($availabilityRow['avail_end_time'])) {
            return [];
        }

        $date = $availabilityRow['date'];
        $availStart = $this->parseTimeString($availabilityRow['avail_start_time']);
        $availEnd = $this->parseTimeString($availabilityRow['avail_end_time']);

        $slots = [];
        $current = $availStart->copy();
        $interval = 30; // 30 minutes

        // Generate 30-minute slots that fall within this availability period
        while ($current->lt($availEnd)) {
            $slotStart = $current->format('H:i');
            $slotEnd = $current->copy()->addMinutes($interval)->format('H:i');

            // Only include slot if it's completely covered by availability
            if ($current->copy()->addMinutes($interval)->lte($availEnd)) {
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
}
