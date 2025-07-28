<?php

namespace App\Services;

use App\Models\Event;
use Carbon\Carbon;

/**
 * GroupAvailabilityBenchmark - Performance testing and comparison utilities.
 *
 * Contains original (unoptimized) implementations for benchmark comparisons.
 * This class is specifically for performance testing and should not be used in production.
 */
class GroupAvailabilityBenchmark
{
    /**
     * Original implementation for performance testing (with N+1 queries).
     * This method demonstrates the performance issues we optimized.
     */
    public function calculateGroupAvailabilityOriginal(Event $event): array
    {
        $groupAvailability = [];
        $totalParticipants = $event->participants->count(); // N+1 query issue

        // Generate all possible 30-minute time slots for each event date
        foreach ($event->timeSlots as $timeSlot) { // Lazy loading issue
            $dateKey = $timeSlot->date->format('Y-m-d');
            $timeSlots = $this->generateTimeSlots($timeSlot->start_time, $timeSlot->end_time);

            foreach ($timeSlots as $slot) {
                // Count participants available for this specific time slot
                $availableParticipants = $this->getAvailableParticipantsOriginal(
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
     * Original method for getting available participants (with N+1 queries).
     */
    private function getAvailableParticipantsOriginal(Event $event, string $date, string $startTime, string $endTime): array
    {
        $availableParticipants = [];

        foreach ($event->participants as $participant) { // N+1 query
            foreach ($participant->participantAvailabilities as $availability) { // Another N+1 query
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
     * Generate 30-minute time slots between start and end time.
     * (Duplicated from GroupAvailabilityService for benchmark independence)
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
     * Check if two time ranges overlap.
     * (Duplicated from GroupAvailabilityService for benchmark independence)
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
     * (Duplicated from GroupAvailabilityService for benchmark independence)
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
