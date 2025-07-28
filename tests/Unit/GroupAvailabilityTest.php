<?php

use App\Models\Event;
use App\Models\EventParticipant;
use App\Models\EventTimeSlot;
use App\Models\ParticipantAvailability;
use App\Services\GroupAvailabilityService;
use Carbon\Carbon;

test('it calculates group availability correctly', function () {
    // Create an event with time slot
    $event = Event::factory()->create(['name' => 'Test Event']);

    EventTimeSlot::factory()->create([
        'event_id' => $event->id,
        'date' => '2025-08-01',
        'start_time' => '09:00:00',
        'end_time' => '11:00:00',
    ]);

    // Create participants
    $participant1 = EventParticipant::factory()->create([
        'event_id' => $event->id,
        'name' => 'Alice',
    ]);

    $participant2 = EventParticipant::factory()->create([
        'event_id' => $event->id,
        'name' => 'Bob',
    ]);

    // Add availability for participants
    ParticipantAvailability::factory()->create([
        'event_id' => $event->id,
        'participant_id' => $participant1->id,
        'date' => '2025-08-01',
        'start_time' => '09:00:00',
        'end_time' => '10:00:00',
    ]);

    ParticipantAvailability::factory()->create([
        'event_id' => $event->id,
        'participant_id' => $participant2->id,
        'date' => '2025-08-01',
        'start_time' => '09:30:00',
        'end_time' => '11:00:00',
    ]);

    // Get group availability through service
    $service = new GroupAvailabilityService;
    $event->load('timeSlots', 'participants.participantAvailabilities');

    $groupAvailability = $service->calculateGroupAvailability($event);

    expect($groupAvailability)->toBeArray();
    expect(count($groupAvailability))->toBeGreaterThan(0);

    // Check first slot (09:00-09:30) - should have 1 participant (Alice)
    $firstSlot = collect($groupAvailability)->first();
    expect($firstSlot['start_time'])->toBe('09:00');
    expect($firstSlot['end_time'])->toBe('09:30');
    expect($firstSlot['available_count'])->toBe(1);
    expect($firstSlot['total_participants'])->toBe(2);
    expect($firstSlot['available_participants'])->toContain('Alice');

    // Check if data structure is correct
    expect($firstSlot)->toHaveKeys([
        'date', 'start_time', 'end_time', 'available_count',
        'total_participants', 'available_participants', 'availability_percentage',
    ]);
});

test('it generates 30-minute time slots correctly', function () {
    $service = new GroupAvailabilityService;
    $reflection = new ReflectionClass($service);
    $method = $reflection->getMethod('generateTimeSlots');
    $method->setAccessible(true);

    $slots = $method->invoke($service, '09:00:00', '11:00:00');

    expect($slots)->toBeArray();
    expect(count($slots))->toBe(4); // 09:00-09:30, 09:30-10:00, 10:00-10:30, 10:30-11:00

    expect($slots[0])->toBe(['start_time' => '09:00', 'end_time' => '09:30']);
    expect($slots[1])->toBe(['start_time' => '09:30', 'end_time' => '10:00']);
    expect($slots[2])->toBe(['start_time' => '10:00', 'end_time' => '10:30']);
    expect($slots[3])->toBe(['start_time' => '10:30', 'end_time' => '11:00']);
});

test('it generates slots from availability correctly', function () {
    $service = new GroupAvailabilityService;
    $reflection = new ReflectionClass($service);
    $method = $reflection->getMethod('generateSlotsFromAvailability');
    $method->setAccessible(true);

    // Test case 1: Availability covers multiple slots
    $availabilityRow = [
        'date' => '2025-08-01',
        'avail_start_time' => '09:00:00',
        'avail_end_time' => '10:30:00',
        'participant_name' => 'Alice',
    ];

    $slots = $method->invoke($service, $availabilityRow);
    expect($slots)->toBeArray();
    expect(count($slots))->toBe(3); // 09:00-09:30, 09:30-10:00, 10:00-10:30

    expect($slots[0]['key'])->toBe('2025-08-01_09:00_09:30');
    expect($slots[1]['key'])->toBe('2025-08-01_09:30_10:00');
    expect($slots[2]['key'])->toBe('2025-08-01_10:00_10:30');

    // Test case 2: Empty availability
    $emptyRow = [
        'date' => '2025-08-01',
        'avail_start_time' => '',
        'avail_end_time' => '',
        'participant_name' => 'Bob',
    ];

    $emptySlots = $method->invoke($service, $emptyRow);
    expect($emptySlots)->toBeArray();
    expect(count($emptySlots))->toBe(0);
});

test('it handles time format parsing correctly', function () {
    $service = new GroupAvailabilityService;
    $reflection = new ReflectionClass($service);
    $method = $reflection->getMethod('parseTimeString');
    $method->setAccessible(true);

    // Test H:i:s format
    $result = $method->invoke($service, '09:30:00');
    expect($result)->toBeInstanceOf(Carbon::class);
    expect($result->format('H:i'))->toBe('09:30');

    // Test H:i format
    $result = $method->invoke($service, '09:30');
    expect($result)->toBeInstanceOf(Carbon::class);
    expect($result->format('H:i'))->toBe('09:30');
});

test('it calculates availability percentage correctly', function () {
    // Create an event with 3 participants
    $event = Event::factory()->create();
    EventTimeSlot::factory()->create([
        'event_id' => $event->id,
        'date' => '2025-08-01',
        'start_time' => '09:00:00',
        'end_time' => '10:00:00',
    ]);

    $participants = EventParticipant::factory()->count(3)->create(['event_id' => $event->id]);

    // Make 2 out of 3 participants available for 09:00-09:30
    ParticipantAvailability::factory()->create([
        'event_id' => $event->id,
        'participant_id' => $participants[0]->id,
        'date' => '2025-08-01',
        'start_time' => '09:00:00',
        'end_time' => '10:00:00',
    ]);

    ParticipantAvailability::factory()->create([
        'event_id' => $event->id,
        'participant_id' => $participants[1]->id,
        'date' => '2025-08-01',
        'start_time' => '09:00:00',
        'end_time' => '10:00:00',
    ]);

    $service = new GroupAvailabilityService;
    $event->load('timeSlots', 'participants.participantAvailabilities');

    $groupAvailability = $service->calculateGroupAvailability($event);

    $firstSlot = collect($groupAvailability)->first();
    expect($firstSlot['available_count'])->toBe(2);
    expect($firstSlot['total_participants'])->toBe(3);
    expect($firstSlot['availability_percentage'])->toBe(67.0); // 2/3 * 100 = 66.67, rounded to 67
});

test('it handles edge cases correctly', function () {
    // Test with no participants
    $event = Event::factory()->create();
    EventTimeSlot::factory()->create([
        'event_id' => $event->id,
        'date' => '2025-08-01',
        'start_time' => '09:00:00',
        'end_time' => '10:00:00',
    ]);

    $service = new GroupAvailabilityService;
    $event->load('timeSlots', 'participants.participantAvailabilities');

    $groupAvailability = $service->calculateGroupAvailability($event);

    expect($groupAvailability)->toBeArray();
    expect(count($groupAvailability))->toBeGreaterThan(0);

    $firstSlot = collect($groupAvailability)->first();
    expect($firstSlot['available_count'])->toBe(0);
    expect($firstSlot['total_participants'])->toBe(0);
    expect($firstSlot['availability_percentage'])->toBe(0);
});
