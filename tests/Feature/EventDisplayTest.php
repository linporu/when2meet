<?php

use App\Models\Event;
use App\Models\EventTimeSlot;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('can display event page with valid hash', function () {
    $event = Event::factory()->create([
        'name' => 'Test Meeting',
    ]);

    EventTimeSlot::factory()->create([
        'event_id' => $event->id,
        'date' => '2025-08-01',
        'start_time' => '09:00:00',
        'end_time' => '17:00:00',
    ]);

    $response = $this->get('/'.$event->hash);

    $response->assertStatus(200);
    $response->assertSee('Test Meeting');
    $response->assertSee('2025-08-01');
    $response->assertSee('09:00');
    $response->assertSee('17:00');
    $response->assertSee('Enter your name');
});

test('returns 404 for non-existent event hash', function () {
    $response = $this->get('/nonexistent');

    $response->assertStatus(404);
});

test('event page contains participant join form', function () {
    $event = Event::factory()->create();

    $response = $this->get('/'.$event->hash);

    $response->assertStatus(200);
    $response->assertSee('name="participant_name"', false);
    $response->assertSee('Join Event');
});
