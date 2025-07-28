<?php

namespace App\Services;

use App\Models\Event;
use Carbon\Carbon;

class GroupAvailabilityService
{
    /**
     * Calculate group availability for all participants in 30-minute slots.
     * Optimized version with single JOIN query and in-memory indexes.
     */
    public function calculateGroupAvailability(Event $event): array
    {

        // Step 1: Load all data in a single optimized query
        $rawData = $event->getGroupAvailabilityData();

        // Step 2: Build in-memory indexes for fast lookups
        $indexes = $this->buildIndexes($rawData);

        // Step 3: Generate group availability data
        $groupAvailability = [];
        $totalParticipants = count($indexes['participants']);

        foreach ($indexes['timeSlots'] as $timeSlotData) {
            $timeSlots = $this->generateTimeSlots(
                $timeSlotData['start_time'],
                $timeSlotData['end_time']
            );

            foreach ($timeSlots as $slot) {
                // Use optimized participant lookup
                $availableParticipants = $this->getAvailableParticipants(
                    $timeSlotData['date'],
                    $slot['start_time'],
                    $slot['end_time'],
                    $indexes
                );

                $groupAvailability[] = [
                    'date' => $timeSlotData['date'],
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
     * Build in-memory indexes for fast lookups from raw data.
     */
    private function buildIndexes(array $rawData): array
    {
        $timeSlots = [];
        $participants = [];
        $participantAvailabilities = [];

        foreach ($rawData as $row) {
            $row = (array) $row;

            // Build time slots index
            $dateKey = $row['date'];
            $slotKey = $dateKey.'_'.$row['slot_start_time'].'_'.$row['slot_end_time'];

            if (! isset($timeSlots[$slotKey])) {
                $timeSlots[$slotKey] = [
                    'date' => $dateKey,
                    'start_time' => $row['slot_start_time'],
                    'end_time' => $row['slot_end_time'],
                ];
            }

            // Build participants index (only if participant exists)
            if (! empty($row['participant_id'])) {
                $participantId = $row['participant_id'];
                $participants[$participantId] = $row['participant_name'];

                // Build participant availability index (only if availability exists)
                if (! empty($row['avail_start_time']) && ! empty($row['avail_end_time'])) {
                    $availKey = $dateKey.'_'.$participantId;
                    if (! isset($participantAvailabilities[$availKey])) {
                        $participantAvailabilities[$availKey] = [];
                    }

                    $participantAvailabilities[$availKey][] = [
                        'start_time' => $row['avail_start_time'],
                        'end_time' => $row['avail_end_time'],
                    ];
                }
            }
        }

        return [
            'timeSlots' => $timeSlots,
            'participants' => $participants,
            'participantAvailabilities' => $participantAvailabilities,
        ];
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
     * Get available participants for a time slot using optimized array lookups.
     */
    private function getAvailableParticipants(string $date, string $startTime, string $endTime, array $indexes): array
    {
        $availableParticipants = [];
        $participants = $indexes['participants'];
        $participantAvailabilities = $indexes['participantAvailabilities'];

        foreach ($participants as $participantId => $participantName) {
            $availKey = $date.'_'.$participantId;

            // Check if this participant has any availability for this date
            if (isset($participantAvailabilities[$availKey])) {
                $availabilities = $participantAvailabilities[$availKey];

                // Check if any of their availability time ranges cover this slot
                foreach ($availabilities as $availability) {
                    if ($this->timeSlotOverlaps(
                        $availability['start_time'],
                        $availability['end_time'],
                        $startTime,
                        $endTime
                    )) {
                        $availableParticipants[] = $participantName;
                        break; // Participant is available, no need to check other ranges
                    }
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
        } catch (\Exception) {
            return Carbon::createFromFormat('H:i', $timeString);
        }
    }
}
