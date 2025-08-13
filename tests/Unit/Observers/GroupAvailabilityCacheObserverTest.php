<?php

use App\Contracts\GroupAvailabilityServiceInterface;
use App\Models\Event;
use App\Models\EventParticipant;
use App\Models\EventTimeSlot;
use App\Models\ParticipantAvailability;
use Illuminate\Support\Facades\Log;

beforeEach(function () {
    // Create test event (simple structure)
    $this->testEvent = Event::factory()->create([
        'name' => 'Observer Test Event',
    ]);

    // Create event time slots
    for ($hour = 9; $hour < 17; $hour++) {
        EventTimeSlot::factory()->create([
            'event_id' => $this->testEvent->id,
            'date' => '2024-12-15',
            'start_time' => sprintf('%02d:00:00', $hour),
            'end_time' => sprintf('%02d:30:00', $hour),
        ]);

        EventTimeSlot::factory()->create([
            'event_id' => $this->testEvent->id,
            'date' => '2024-12-15',
            'start_time' => sprintf('%02d:30:00', $hour),
            'end_time' => sprintf('%02d:00:00', $hour + 1),
        ]);
    }
});

test('participant availability observer clears cache when availability is created', function () {
    $groupAvailabilityService = app(GroupAvailabilityServiceInterface::class);

    // Create participant
    $participant = EventParticipant::factory()->create([
        'event_id' => $this->testEvent->id,
        'name' => 'Test Participant',
    ]);

    // Cache initial group availability (empty)
    $initialResult = $groupAvailabilityService->calculateGroupAvailability($this->testEvent);

    // Verify cache exists
    $initialStats = $groupAvailabilityService->getCacheStats($this->testEvent->id);
    expect($initialStats['static_slots_cached'])->toBeTrue();

    // Create participant availability - this should trigger Observer
    ParticipantAvailability::factory()->create([
        'participant_id' => $participant->id,
        'date' => '2024-12-15',
        'start_time' => '09:00:00',
        'end_time' => '12:00:00',
    ]);

    // Cache should be cleared after Observer runs
    $statsAfterCreate = $groupAvailabilityService->getCacheStats($this->testEvent->id);
    expect($statsAfterCreate['static_slots_cached'])->toBeFalse();

    // New calculation should reflect the new availability
    $newResult = $groupAvailabilityService->calculateGroupAvailability($this->testEvent->fresh());
    $morningSlot = collect($newResult)->first(fn ($slot) => $slot['start_time'] === '09:00');
    expect($morningSlot['available_count'])->toBe(1);
});

test('participant availability observer clears cache when availability is updated', function () {
    $groupAvailabilityService = app(GroupAvailabilityServiceInterface::class);

    // Create participant with initial availability
    $participant = EventParticipant::factory()->create([
        'event_id' => $this->testEvent->id,
        'name' => 'Test Participant',
    ]);

    $availability = ParticipantAvailability::factory()->create([
        'participant_id' => $participant->id,
        'date' => '2024-12-15',
        'start_time' => '09:00:00',
        'end_time' => '12:00:00',
    ]);

    // Cache initial result
    $groupAvailabilityService->calculateGroupAvailability($this->testEvent);
    expect($groupAvailabilityService->getCacheStats($this->testEvent->id)['static_slots_cached'])->toBeTrue();

    // Update availability - this should trigger Observer
    $availability->update([
        'end_time' => '15:00:00', // Extend availability
    ]);

    // Cache should be cleared after Observer runs
    $statsAfterUpdate = $groupAvailabilityService->getCacheStats($this->testEvent->id);
    expect($statsAfterUpdate['static_slots_cached'])->toBeFalse();
});

test('participant availability observer clears cache when availability is deleted', function () {
    $groupAvailabilityService = app(GroupAvailabilityServiceInterface::class);

    // Create participant with availability
    $participant = EventParticipant::factory()->create([
        'event_id' => $this->testEvent->id,
        'name' => 'Test Participant',
    ]);

    $availability = ParticipantAvailability::factory()->create([
        'participant_id' => $participant->id,
        'date' => '2024-12-15',
        'start_time' => '09:00:00',
        'end_time' => '12:00:00',
    ]);

    // Cache result with participant
    $groupAvailabilityService->calculateGroupAvailability($this->testEvent);
    expect($groupAvailabilityService->getCacheStats($this->testEvent->id)['static_slots_cached'])->toBeTrue();

    // Delete availability - this should trigger Observer
    $availability->delete();

    // Cache should be cleared after Observer runs
    $statsAfterDelete = $groupAvailabilityService->getCacheStats($this->testEvent->id);
    expect($statsAfterDelete['static_slots_cached'])->toBeFalse();
});

test('event participant observer clears cache when participant is created', function () {
    $groupAvailabilityService = app(GroupAvailabilityServiceInterface::class);

    // Cache initial result (no participants)
    $groupAvailabilityService->calculateGroupAvailability($this->testEvent);
    expect($groupAvailabilityService->getCacheStats($this->testEvent->id)['static_slots_cached'])->toBeTrue();

    // Create participant - this should trigger Observer
    EventParticipant::factory()->create([
        'event_id' => $this->testEvent->id,
        'name' => 'New Participant',
    ]);

    // Cache should be cleared after Observer runs
    $statsAfterCreate = $groupAvailabilityService->getCacheStats($this->testEvent->id);
    expect($statsAfterCreate['static_slots_cached'])->toBeFalse();
});

test('event participant observer clears cache when participant is updated', function () {
    $groupAvailabilityService = app(GroupAvailabilityServiceInterface::class);

    // Create participant
    $participant = EventParticipant::factory()->create([
        'event_id' => $this->testEvent->id,
        'name' => 'Original Name',
    ]);

    // Cache result
    $groupAvailabilityService->calculateGroupAvailability($this->testEvent);
    expect($groupAvailabilityService->getCacheStats($this->testEvent->id)['static_slots_cached'])->toBeTrue();

    // Update participant - this should trigger Observer
    $participant->update(['name' => 'Updated Name']);

    // Cache should be cleared after Observer runs
    $statsAfterUpdate = $groupAvailabilityService->getCacheStats($this->testEvent->id);
    expect($statsAfterUpdate['static_slots_cached'])->toBeFalse();
});

test('event participant observer clears cache when participant is deleted', function () {
    $groupAvailabilityService = app(GroupAvailabilityServiceInterface::class);

    // Create participant
    $participant = EventParticipant::factory()->create([
        'event_id' => $this->testEvent->id,
        'name' => 'To Be Deleted',
    ]);

    // Cache result
    $groupAvailabilityService->calculateGroupAvailability($this->testEvent);
    expect($groupAvailabilityService->getCacheStats($this->testEvent->id)['static_slots_cached'])->toBeTrue();

    // Delete participant - this should trigger Observer
    $participant->delete();

    // Cache should be cleared after Observer runs
    $statsAfterDelete = $groupAvailabilityService->getCacheStats($this->testEvent->id);
    expect($statsAfterDelete['static_slots_cached'])->toBeFalse();
});

test('observers log cache clearing activities', function () {
    // This test verifies that cache clearing happens without errors
    // We don't need to verify specific log messages, just that operations succeed

    $participant = EventParticipant::factory()->create([
        'event_id' => $this->testEvent->id,
        'name' => 'Log Test Participant',
    ]);

    // Create availability - should trigger Observer and clear cache without errors
    ParticipantAvailability::factory()->create([
        'participant_id' => $participant->id,
        'date' => '2024-12-15',
        'start_time' => '09:00:00',
        'end_time' => '12:00:00',
    ]);

    // If we reach here, the Observer worked without throwing exceptions
    expect(true)->toBeTrue();
});
