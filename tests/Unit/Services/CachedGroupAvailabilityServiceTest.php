<?php

use App\Models\Event;
use App\Models\EventParticipant;
use App\Models\EventTimeSlot;
use App\Models\ParticipantAvailability;
use App\Services\CachedGroupAvailabilityService;
use App\Services\GroupAvailabilityService;
use Illuminate\Cache\Repository as CacheRepository;

beforeEach(function () {
    $this->baseService = new GroupAvailabilityService;
    $this->cachedService = new CachedGroupAvailabilityService(
        $this->baseService,
        app(CacheRepository::class)
    );

    // Create test event (simple structure)
    $this->testEvent = Event::factory()->create([
        'name' => 'Test Event',
    ]);

    // Create event time slots
    for ($hour = 9; $hour < 17; $hour++) {
        for ($minute = 0; $minute < 60; $minute += 30) {
            $startTime = sprintf('%02d:%02d:00', $hour, $minute);
            $endTime = sprintf('%02d:%02d:00', $hour, $minute + 30);
            if ($hour === 16 && $minute === 30) {
                $endTime = '17:00:00';
            }

            EventTimeSlot::factory()->create([
                'event_id' => $this->testEvent->id,
                'date' => '2024-12-15',
                'start_time' => $startTime,
                'end_time' => $endTime,
            ]);
        }
    }
});

test('cache mechanism works correctly', function () {
    // First call should miss cache and calculate normally
    $result1 = $this->cachedService->calculateGroupAvailability($this->testEvent);

    // Verify result structure
    expect($result1)->toBeArray()->not->toBeEmpty();
    expect($result1[0])->toHaveKeys(['date', 'available_count']);

    // Second call should hit cache
    $result2 = $this->cachedService->calculateGroupAvailability($this->testEvent);

    // Results should be identical
    expect($result1)->toEqual($result2);
});

test('cache cleared when participant availability changes', function () {
    // Create participant with availability
    $participant = EventParticipant::factory()->create([
        'event_id' => $this->testEvent->id,
        'name' => 'Test Participant',
    ]);

    ParticipantAvailability::factory()->create([
        'participant_id' => $participant->id,
        'date' => '2024-12-15',
        'start_time' => '09:00:00',
        'end_time' => '12:00:00',
    ]);

    // First call - cache the result
    $result1 = $this->cachedService->calculateGroupAvailability($this->testEvent->fresh());

    // Verify participant is in result
    $morningSlot = collect($result1)->first(fn ($slot) => $slot['start_time'] === '09:00');
    expect($morningSlot)->not->toBeNull();
    expect($morningSlot['available_count'])->toBe(1);

    // Add more availability for the same participant
    ParticipantAvailability::factory()->create([
        'participant_id' => $participant->id,
        'date' => '2024-12-15',
        'start_time' => '14:00:00',
        'end_time' => '16:00:00',
    ]);

    // Clear cache manually (simulating what Observer should do)
    $this->cachedService->clearEventCache($this->testEvent->id);

    // Second call should reflect new availability
    $result2 = $this->cachedService->calculateGroupAvailability($this->testEvent->fresh());

    $afternoonSlot = collect($result2)->first(fn ($slot) => $slot['start_time'] === '14:00');
    expect($afternoonSlot)->not->toBeNull();
    expect($afternoonSlot['available_count'])->toBe(1);
});

test('multiple events have isolated cache', function () {
    // Create second event
    $event2 = Event::factory()->create([
        'name' => 'Test Event 2',
    ]);

    // Create time slots for second event
    EventTimeSlot::factory()->create([
        'event_id' => $event2->id,
        'date' => '2024-12-16',
        'start_time' => '10:00:00',
        'end_time' => '15:00:00',
    ]);

    // Create participants for both events
    $participant1 = EventParticipant::factory()->create([
        'event_id' => $this->testEvent->id,
        'name' => 'Participant Event 1',
    ]);

    $participant2 = EventParticipant::factory()->create([
        'event_id' => $event2->id,
        'name' => 'Participant Event 2',
    ]);

    // Cache results for both events
    $result1 = $this->cachedService->calculateGroupAvailability($this->testEvent);
    $result2 = $this->cachedService->calculateGroupAvailability($event2);

    // Clear cache for event1 only
    $this->cachedService->clearEventCache($this->testEvent->id);

    // Verify cache isolation by checking cache stats
    $stats1 = $this->cachedService->getCacheStats($this->testEvent->id);
    $stats2 = $this->cachedService->getCacheStats($event2->id);

    // Event1 cache should be cleared
    expect($stats1['static_slots_cached'])->toBeFalse();
});

test('cache stats provide useful information', function () {
    // Test cache stats before any caching
    $stats = $this->cachedService->getCacheStats($this->testEvent->id);

    expect($stats)->toBeArray();
    expect($stats)->toHaveKeys(['static_slots_cached', 'static_slots_key', 'cache_strategy']);
    expect($stats['static_slots_cached'])->toBeFalse();
    expect($stats['cache_strategy'])->toBe('direct_key_management');

    // Call the method to cache something
    $this->cachedService->calculateGroupAvailability($this->testEvent);

    // Check stats after caching
    $statsAfter = $this->cachedService->getCacheStats($this->testEvent->id);
    expect($statsAfter['static_slots_cached'])->toBeTrue();
});

test('clear event cache removes all related cache', function () {
    // Create participant and availability
    $participant = EventParticipant::factory()->create([
        'event_id' => $this->testEvent->id,
        'name' => 'Test Participant',
    ]);

    ParticipantAvailability::factory()->create([
        'participant_id' => $participant->id,
        'date' => '2024-12-15',
        'start_time' => '09:00:00',
        'end_time' => '12:00:00',
    ]);

    // Cache the result
    $this->cachedService->calculateGroupAvailability($this->testEvent);

    // Verify cache exists
    $statsBefore = $this->cachedService->getCacheStats($this->testEvent->id);
    expect($statsBefore['static_slots_cached'])->toBeTrue();

    // Clear cache
    $this->cachedService->clearEventCache($this->testEvent->id);

    // Verify cache is cleared
    $statsAfter = $this->cachedService->getCacheStats($this->testEvent->id);
    expect($statsAfter['static_slots_cached'])->toBeFalse();
});
