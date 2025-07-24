<?php

use App\Models\Event;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('homepage displays event creation form', function () {
    $response = $this->get('/');

    $response->assertStatus(200);
    $response->assertSee('Create New Event');
    $response->assertSee('name="event_name"', false);
    $response->assertSee('name="date"', false);
    $response->assertSee('name="start_time"', false);
    $response->assertSee('name="end_time"', false);
    $response->assertSee('name="timezone"', false);
});

test('can create event with valid data', function () {
    $eventData = [
        'event_name' => 'Test Event',
        'date' => '2025-08-01',
        'start_time' => '09:00',
        'end_time' => '17:00',
        'timezone' => 'Asia/Taipei',
    ];

    $response = $this->post('/', $eventData);

    expect(Event::count())->toBe(1);

    $event = Event::first();
    expect($event->name)->toBe('Test Event');

    $response->assertRedirect('/'.$event->hash);
});

test('validates required fields', function () {
    $response = $this->post('/', []);

    $response->assertSessionHasErrors(['event_name']);
});

test('validates date format', function () {
    $response = $this->post('/', [
        'event_name' => 'Test Event',
        'date' => 'invalid-date',
    ]);

    $response->assertSessionHasErrors(['date']);
});
