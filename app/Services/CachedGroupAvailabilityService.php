<?php

namespace App\Services;

use App\Contracts\GroupAvailabilityServiceInterface;
use App\Models\Event;
use Illuminate\Cache\Repository as CacheRepository;
use Illuminate\Support\Facades\Log;

class CachedGroupAvailabilityService implements GroupAvailabilityServiceInterface
{
    protected GroupAvailabilityServiceInterface $baseService;

    protected CacheRepository $cache;

    protected int $staticSlotsTtl = 86400; // 24 hours

    protected int $dynamicResultTtl = 1800; // 30 minutes

    public function __construct(GroupAvailabilityServiceInterface $baseService, CacheRepository $cache)
    {
        $this->baseService = $baseService;
        $this->cache = $cache;
    }

    public function calculateGroupAvailability(Event $event): array
    {
        $startTime = microtime(true);

        // Generate cache keys
        $staticSlotsKey = $this->getStaticSlotsKey($event->id);
        $participantsHash = $this->calculateParticipantsHash($event);
        $fullResultKey = $this->getFullResultKey($event->id, $participantsHash);

        // Check for cached complete result first
        if ($this->cache->has($fullResultKey)) {
            $this->logCacheHit('full_result', $event->id, microtime(true) - $startTime);

            return $this->cache->get($fullResultKey);
        }

        // Try to get cached static slots
        $allTimeSlots = $this->cache->get($staticSlotsKey);

        if ($allTimeSlots === null) {
            // No cached static slots, calculate everything normally
            $result = $this->baseService->calculateGroupAvailability($event);

            // Extract and cache static slots for future use
            $allTimeSlots = $this->extractStaticSlots($result);
            $this->cacheStaticSlots($staticSlotsKey, $allTimeSlots);

            // Cache complete result
            $this->cacheFullResult($fullResultKey, $result);

            $this->logCacheHit('miss_full_calculation', $event->id, microtime(true) - $startTime);

            return $result;
        }

        // We have cached static slots, calculate only dynamic parts
        $result = $this->calculateWithCachedStaticSlots($event, $allTimeSlots);

        // Cache the complete result
        $this->cacheFullResult($fullResultKey, $result);

        $this->logCacheHit('partial_cache_hit', $event->id, microtime(true) - $startTime);

        return $result;
    }

    protected function calculateWithCachedStaticSlots(Event $event, array $allTimeSlots): array
    {
        // Load participant data
        $event->loadMissing('participants.participantAvailabilities');

        // Calculate dynamic parts
        $totalParticipants = $event->participants->count();
        $participantSlots = $this->getParticipantSlots($event);

        // Merge static slots with dynamic participant data
        return collect($allTimeSlots)
            ->map(function ($slot) use ($participantSlots, $totalParticipants) {
                $slotKey = $slot['key'];
                $availableParticipants = $participantSlots->get($slotKey, collect())
                    ->pluck('participant')
                    ->unique()
                    ->values()
                    ->toArray();

                $availableCount = count($availableParticipants);

                return [
                    'date' => $slot['date'],
                    'start_time' => $slot['start_time'],
                    'end_time' => $slot['end_time'],
                    'available_participants' => $availableParticipants,
                    'available_count' => $availableCount,
                    'total_participants' => $totalParticipants,
                    'availability_percentage' => $totalParticipants > 0
                        ? round(($availableCount / $totalParticipants) * 100)
                        : 0,
                ];
            })
            ->values()
            ->toArray();
    }

    protected function getParticipantSlots(Event $event)
    {
        $slots = collect();

        foreach ($event->participants as $participant) {
            foreach ($participant->participantAvailabilities as $availability) {
                $participantSlots = $this->generateParticipantSlots([
                    'date' => $availability->date,
                    'participant_name' => $participant->name,
                    'avail_start_time' => $availability->start_time,
                    'avail_end_time' => $availability->end_time,
                ]);

                foreach ($participantSlots as $slot) {
                    $slots->push($slot);
                }
            }
        }

        return $slots->groupBy('key');
    }

    protected function generateParticipantSlots(array $availabilityRow): array
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

    protected function parseTimeString(string $timeString)
    {
        try {
            return \Carbon\Carbon::createFromFormat('H:i:s', $timeString);
        } catch (\Exception) {
            return \Carbon\Carbon::createFromFormat('H:i', $timeString);
        }
    }

    protected function extractStaticSlots(array $result): array
    {
        return collect($result)
            ->map(fn ($slot) => [
                'key' => $slot['date'].'_'.$slot['start_time'].'_'.$slot['end_time'],
                'date' => $slot['date'],
                'start_time' => $slot['start_time'],
                'end_time' => $slot['end_time'],
            ])
            ->toArray();
    }

    protected function calculateParticipantsHash(Event $event): string
    {
        // Load participants with their availability data
        $event->loadMissing('participants.participantAvailabilities');

        // Create hash based on participant data and their last update times
        $participantData = $event->participants->map(function ($participant) {
            return [
                'id' => $participant->id,
                'name' => $participant->name,
                'updated_at' => $participant->updated_at->timestamp,
                'availabilities' => $participant->participantAvailabilities->map(fn ($avail) => [
                    'date' => $avail->date,
                    'start_time' => $avail->start_time,
                    'end_time' => $avail->end_time,
                    'updated_at' => $avail->updated_at->timestamp,
                ])->toArray(),
            ];
        })->toArray();

        return md5(json_encode($participantData));
    }

    protected function getStaticSlotsKey(int $eventId): string
    {
        return "event_time_slots:{$eventId}";
    }

    protected function getFullResultKey(int $eventId, string $participantsHash): string
    {
        return "group_availability:{$eventId}:{$participantsHash}";
    }

    protected function cacheStaticSlots(string $key, array $data): void
    {
        $this->cache->put($key, $data, $this->staticSlotsTtl);
    }

    protected function cacheFullResult(string $key, array $data): void
    {
        $this->cache->put($key, $data, $this->dynamicResultTtl);
    }

    protected function logCacheHit(string $type, int $eventId, float $executionTime): void
    {
        Log::info('GroupAvailabilityCache', [
            'type' => $type,
            'event_id' => $eventId,
            'execution_time_ms' => round($executionTime * 1000, 2),
        ]);
    }

    public function clearEventCache(int $eventId): void
    {
        $this->clearAllEventCache($eventId);
    }

    protected function clearAllEventCache(int $eventId): void
    {
        // Clear static slots cache
        $staticSlotsKey = $this->getStaticSlotsKey($eventId);
        $this->cache->forget($staticSlotsKey);

        // Clear all dynamic group availability cache keys
        // Since we can't pattern match, we'll rely on natural TTL expiration
        // and clear the most recent cache if we can determine the current hash
        try {
            $event = \App\Models\Event::find($eventId);
            if ($event) {
                $currentHash = $this->calculateParticipantsHash($event);
                $currentResultKey = $this->getFullResultKey($eventId, $currentHash);
                $this->cache->forget($currentResultKey);
            }
        } catch (\Exception $e) {
            // If we can't determine current hash, that's okay
            // Old cache entries will expire naturally
            Log::debug('Could not clear specific group availability cache', [
                'event_id' => $eventId,
                'error' => $e->getMessage(),
            ]);
        }

        Log::info('EventCache cleared', [
            'event_id' => $eventId,
            'static_slots_key' => $staticSlotsKey,
        ]);
    }

    public function getCacheStats(int $eventId): array
    {
        $staticSlotsKey = $this->getStaticSlotsKey($eventId);

        return [
            'static_slots_cached' => $this->cache->has($staticSlotsKey),
            'static_slots_key' => $staticSlotsKey,
            'cache_strategy' => 'direct_key_management',
        ];
    }
}
