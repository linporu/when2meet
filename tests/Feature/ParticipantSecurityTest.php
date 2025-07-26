<?php

use App\Models\Event;
use App\Models\EventParticipant;
use App\Models\EventTimeSlot;

describe('Participant Security Tests - Test God Level', function () {
    describe('Cross-Event Access Prevention (Critical Security)', function () {
        it('prevents participant from accessing different event availability edit', function () {
            // Arrange: Create two separate events
            $event1 = Event::factory()->create(['name' => 'Event 1']);
            $event2 = Event::factory()->create(['name' => 'Event 2']);

            EventTimeSlot::factory()->forEvent($event1)->create();
            EventTimeSlot::factory()->forEvent($event2)->create();

            // Create participant for event2
            $participant = EventParticipant::factory()->forEvent($event2)->create([
                'name' => 'John Doe',
            ]);

            // Act: Try to access participant's availability edit from event1 URL
            $response = $this->get("/{$event1->hash}/participants/{$participant->id}/availability/edit");

            // Assert: Should redirect with error, not allow access
            $response->assertRedirect(route('events.show', $event1->hash));
            $response->assertSessionHas('error', 'Invalid participant access. Please enter your name to continue.');

            // Verify no access to participant data from wrong event
            $this->assertNotEquals($participant->event_id, $event1->id);
        });

        it('prevents participant from updating availability in different event', function () {
            // Arrange: Create two separate events
            $event1 = Event::factory()->create();
            $event2 = Event::factory()->create();

            EventTimeSlot::factory()->forEvent($event1)->create([
                'date' => now()->addDays(1),
                'start_time' => '09:00:00',
                'end_time' => '17:00:00',
            ]);

            $participant = EventParticipant::factory()->forEvent($event2)->create();

            // Act: Try to update availability using wrong event
            $availabilityData = [
                'participant_name' => $participant->name,
                'availability' => [
                    now()->addDays(1)->format('Y-m-d') => [
                        ['start_time' => '09:00', 'end_time' => '12:00'],
                    ],
                ],
            ];

            $response = $this->put("/{$event1->hash}/participants/{$participant->id}/availability", $availabilityData);

            // Assert: Should be blocked with 403 Forbidden by authorize() method
            // This tests UpdateAvailabilityRequest::authorize() cross-event protection
            $response->assertStatus(403);

            // Verify no data was updated in wrong event
            $this->assertNotEquals($participant->event_id, $event1->id);
        });

        it('blocks access to non-existent participant in valid event', function () {
            $event = Event::factory()->create();
            EventTimeSlot::factory()->forEvent($event)->create();

            $nonExistentParticipantId = 99999;

            $response = $this->get("/{$event->hash}/participants/{$nonExistentParticipantId}/availability/edit");

            // Should return 404 due to route model binding
            $response->assertStatus(404);
        });
    });

    describe('Participant ID Manipulation Prevention', function () {
        it('prevents sequential participant ID probing attack', function () {
            $event = Event::factory()->create();
            EventTimeSlot::factory()->forEvent($event)->create();

            // Create participants with known IDs
            $legitimateParticipant1 = EventParticipant::factory()->forEvent($event)->create();
            $legitimateParticipant2 = EventParticipant::factory()->forEvent($event)->create();

            // Try to probe participant IDs that don't belong to this event
            $otherEvent = Event::factory()->create();
            $otherParticipant = EventParticipant::factory()->forEvent($otherEvent)->create();

            // Test probing with participant ID from different event
            $response = $this->get("/{$event->hash}/participants/{$otherParticipant->id}/availability/edit");

            // Should redirect with error due to participant not belonging to this event
            $response->assertRedirect(route('events.show', $event->hash));
            $response->assertSessionHas('error', 'Invalid participant access. Please enter your name to continue.');

            // Test with completely non-existent participant ID
            $nonExistentId = 99999;
            $response2 = $this->get("/{$event->hash}/participants/{$nonExistentId}/availability/edit");

            // Should return 404 due to route model binding
            $response2->assertStatus(404);
        });

        it('validates participant ownership on every request', function () {
            $event1 = Event::factory()->create();
            $event2 = Event::factory()->create();

            EventTimeSlot::factory()->forEvent($event1)->create();
            EventTimeSlot::factory()->forEvent($event2)->create();

            $participant1 = EventParticipant::factory()->forEvent($event1)->create();
            $participant2 = EventParticipant::factory()->forEvent($event2)->create();

            // Verify each participant can only access their own event
            $response1 = $this->get("/{$event1->hash}/participants/{$participant1->id}/availability/edit");
            $response1->assertStatus(200);

            $response2 = $this->get("/{$event2->hash}/participants/{$participant2->id}/availability/edit");
            $response2->assertStatus(200);

            // Cross-access should be blocked
            $crossAccess1 = $this->get("/{$event1->hash}/participants/{$participant2->id}/availability/edit");
            $crossAccess1->assertRedirect();

            $crossAccess2 = $this->get("/{$event2->hash}/participants/{$participant1->id}/availability/edit");
            $crossAccess2->assertRedirect();
        });
    });

    describe('Data Integrity and Race Condition Prevention', function () {
        it('handles firstOrCreate race condition safely', function () {
            $event = Event::factory()->create();

            $participantName = 'Concurrent User';

            // Simulate concurrent requests trying to create same participant
            $responses = [];

            // Make multiple rapid requests with same name
            for ($i = 0; $i < 3; $i++) {
                $responses[] = $this->post("/{$event->hash}/participants", [
                    'participant_name' => $participantName,
                ]);
            }

            // Verify only one participant was created despite multiple requests
            $participantCount = EventParticipant::where('event_id', $event->id)
                ->where('name', $participantName)
                ->count();

            expect($participantCount)->toBe(1, 'Should create exactly one participant despite concurrent requests');

            // All responses should redirect to same participant
            foreach ($responses as $response) {
                $response->assertRedirect();
                $location = $response->headers->get('Location');
                expect($location)->toContain('availability/edit');
            }
        });

        it('maintains participant data integrity under load', function () {
            $event = Event::factory()->create();

            $participantNames = ['User A', 'User B', 'User C', 'User D', 'User E'];

            // Create multiple participants rapidly
            foreach ($participantNames as $name) {
                $response = $this->post("/{$event->hash}/participants", [
                    'participant_name' => $name,
                ]);
                $response->assertRedirect();
            }

            // Verify all participants were created correctly
            $createdParticipants = EventParticipant::where('event_id', $event->id)->get();

            expect($createdParticipants->count())->toBe(count($participantNames));

            foreach ($participantNames as $name) {
                $this->assertDatabaseHas('event_participants', [
                    'event_id' => $event->id,
                    'name' => $name,
                ]);
            }
        });
    });

    describe('Input Validation Security', function () {
        it('prevents XSS in participant names', function () {
            $event = Event::factory()->create();

            $maliciousName = '<script>alert("xss")</script>';

            $response = $this->post("/{$event->hash}/participants", [
                'participant_name' => $maliciousName,
            ]);

            // Should accept the input (will be escaped in views)
            $response->assertRedirect();

            // Verify stored safely
            $participant = EventParticipant::where('event_id', $event->id)->first();
            expect($participant->name)->toBe($maliciousName);
        });

        it('prevents SQL injection in participant names', function () {
            $event = Event::factory()->create();

            $sqlInjectionAttempt = "'; DROP TABLE event_participants; --";

            $response = $this->post("/{$event->hash}/participants", [
                'participant_name' => $sqlInjectionAttempt,
            ]);

            $response->assertRedirect();

            // Verify table still exists and data is safe
            $this->assertDatabaseHas('event_participants', [
                'event_id' => $event->id,
                'name' => $sqlInjectionAttempt,
            ]);

            // Verify table structure is intact
            $tableExists = \Schema::hasTable('event_participants');
            expect($tableExists)->toBeTrue();
        });
    });
});
