<?php

use App\Models\Event;

describe('EventController Feature Tests', function () {
    describe('Homepage (GET /)', function () {
        it('displays the create event page', function () {
            $response = $this->get('/');

            $response->assertStatus(200);
            $response->assertViewIs('create-event');
        });
    });

    describe('Creating Events (POST /)', function () {
        it('creates a new event successfully with valid data', function () {
            $eventData = [
                'event_name' => 'Team Weekly Meeting',
                'date' => now()->addDays(7)->format('Y-m-d'), // Future date
                'start_time' => '09:00',
                'end_time' => '17:00',
                'timezone' => 'UTC',
            ];

            $response = $this->post('/', $eventData);

            // Should redirect to the event page
            $response->assertStatus(302);

            // Verify event was created in database
            $this->assertDatabaseHas('events', [
                'name' => 'Team Weekly Meeting',
            ]);

            // Should redirect to event show page using hash
            $event = Event::where('name', 'Team Weekly Meeting')->first();

            // Verify time slot was created
            $timeSlot = \App\Models\EventTimeSlot::where('event_id', $event->id)->first();
            expect($timeSlot)->not->toBeNull();
            expect($timeSlot->date->format('Y-m-d'))->toBe(now()->addDays(7)->format('Y-m-d'));
            expect($timeSlot->start_time)->toBe('09:00:00');
            expect($timeSlot->end_time)->toBe('17:00:00');
            $response->assertRedirect('/'.$event->hash);
        });

        it('fails validation with missing required fields', function () {
            $response = $this->post('/', []);

            $response->assertStatus(302); // Redirect back with errors
            $response->assertSessionHasErrors(['event_name', 'date', 'start_time', 'end_time', 'timezone']);
        });

        it('fails validation with invalid date format', function () {
            $eventData = [
                'event_name' => 'Test Event',
                'date' => 'invalid-date',
                'start_time' => '09:00',
                'end_time' => '17:00',
                'timezone' => 'UTC',
            ];

            $response = $this->post('/', $eventData);

            $response->assertStatus(302);
            $response->assertSessionHasErrors(['date']);
        });

        it('fails validation when end time is before start time', function () {
            $eventData = [
                'event_name' => 'Test Event',
                'date' => now()->addDays(7)->format('Y-m-d'),
                'start_time' => '17:00',
                'end_time' => '09:00', // End before start
                'timezone' => 'UTC',
            ];

            $response = $this->post('/', $eventData);

            $response->assertStatus(302);
            $response->assertSessionHasErrors();
        });
    });

    describe('Viewing Events (GET /{hash})', function () {
        it('displays event details for valid hash', function () {
            $event = Event::factory()->create([
                'name' => 'Test Event',
            ]);

            // Create a time slot for this event
            \App\Models\EventTimeSlot::factory()->forEvent($event)->create();

            $response = $this->get('/'.$event->hash);

            $response->assertStatus(200);
            $response->assertViewIs('event-show');
            $response->assertViewHas('event');
            $response->assertSee('Test Event');
        });

        it('returns 404 for invalid hash', function () {
            $response = $this->get('/invalid-hash');

            $response->assertStatus(404);
        });

        it('loads time slots with the event', function () {
            $event = Event::factory()->create();
            \App\Models\EventTimeSlot::factory()->forEvent($event)->create();

            $response = $this->get('/'.$event->hash);

            $response->assertStatus(200);
            $viewData = $response->original->getData();
            expect($viewData['event']->relationLoaded('timeSlots'))->toBeTrue();
        });
    });
});
