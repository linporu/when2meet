<?php

use App\Models\Event;
use App\Models\EventParticipant;
use App\Models\EventTimeSlot;
use App\Models\ParticipantAvailability;

describe('ParticipantController Feature Tests', function () {
    describe('Setting Participant Name (POST /{event:hash}/participants)', function () {
        it('creates new participant and redirects to availability edit', function () {
            // Arrange
            $event = Event::factory()->create(['name' => 'Team Meeting']);
            EventTimeSlot::factory()->forEvent($event)->create();

            $participantName = 'John Doe';

            // Act
            $response = $this->post("/{$event->hash}/participants", [
                'participant_name' => $participantName,
            ]);

            // Assert
            $response->assertStatus(302);

            // Verify participant was created
            $this->assertDatabaseHas('event_participants', [
                'event_id' => $event->id,
                'name' => $participantName,
            ]);

            // Should redirect to availability edit page
            $participant = EventParticipant::where('event_id', $event->id)
                ->where('name', $participantName)
                ->first();

            $response->assertRedirect(route('participants.availability.edit', [
                'event' => $event->hash,
                'participant' => $participant->id,
            ]));
        });

        it('finds existing participant and redirects to availability edit', function () {
            // Arrange
            $event = Event::factory()->create();
            EventTimeSlot::factory()->forEvent($event)->create();

            // Create existing participant
            $existingParticipant = EventParticipant::factory()->forEvent($event)->create([
                'name' => 'Jane Smith',
            ]);

            // Act: Try to "create" participant with same name
            $response = $this->post("/{$event->hash}/participants", [
                'participant_name' => 'Jane Smith',
            ]);

            // Assert
            $response->assertStatus(302);

            // Should not create duplicate participant
            $participantCount = EventParticipant::where('event_id', $event->id)
                ->where('name', 'Jane Smith')
                ->count();
            expect($participantCount)->toBe(1);

            // Should redirect to existing participant's edit page
            $response->assertRedirect(route('participants.availability.edit', [
                'event' => $event->hash,
                'participant' => $existingParticipant->id,
            ]));
        });

        it('fails validation with missing participant name', function () {
            $event = Event::factory()->create();

            $response = $this->post("/{$event->hash}/participants", []);

            $response->assertStatus(302);
            $response->assertSessionHasErrors(['participant_name']);
        });

        it('fails validation with empty participant name', function () {
            $event = Event::factory()->create();

            $response = $this->post("/{$event->hash}/participants", [
                'participant_name' => '',
            ]);

            $response->assertStatus(302);
            $response->assertSessionHasErrors(['participant_name']);
        });

        it('fails validation with too long participant name', function () {
            $event = Event::factory()->create();

            $longName = str_repeat('a', 256); // Exceeds 255 character limit

            $response = $this->post("/{$event->hash}/participants", [
                'participant_name' => $longName,
            ]);

            $response->assertStatus(302);
            $response->assertSessionHasErrors(['participant_name']);
        });

        it('handles special characters in participant names', function () {
            $event = Event::factory()->create();
            EventTimeSlot::factory()->forEvent($event)->create();

            $specialNames = [
                'José María',
                'مصطفى أحمد',
                '田中太郎',
                'O\'Connor',
                'Smith-Jones',
                'User@Company',
                'Test & Development',
            ];

            foreach ($specialNames as $name) {
                $response = $this->post("/{$event->hash}/participants", [
                    'participant_name' => $name,
                ]);

                $response->assertStatus(302);
                $this->assertDatabaseHas('event_participants', [
                    'event_id' => $event->id,
                    'name' => $name,
                ]);
            }
        });

        it('returns 404 for invalid event hash', function () {
            $response = $this->post('/invalid-hash/participants', [
                'participant_name' => 'Test User',
            ]);

            $response->assertStatus(404);
        });
    });

    describe('Showing Availability Edit (GET /{event:hash}/participants/{participant}/availability/edit)', function () {
        it('displays availability edit form for valid participant', function () {
            // Arrange
            $event = Event::factory()->create(['name' => 'Team Planning']);
            $timeSlot = EventTimeSlot::factory()->forEvent($event)->create([
                'date' => now()->addDays(1),
                'start_time' => '09:00:00',
                'end_time' => '17:00:00',
            ]);
            $participant = EventParticipant::factory()->forEvent($event)->create([
                'name' => 'Alice Johnson',
            ]);

            // Act
            $response = $this->get("/{$event->hash}/participants/{$participant->id}/availability/edit");

            // Assert
            $response->assertStatus(200);
            $response->assertViewIs('participant-edit');
            $response->assertViewHas(['event', 'participant', 'existingAvailability', 'timeOptions']);

            // Verify view data contains correct information
            $viewData = $response->original->getData();
            expect($viewData['event']->id)->toBe($event->id);
            expect($viewData['participant']->id)->toBe($participant->id);
            expect($viewData['timeOptions'])->toBeArray();
        });

        it('loads existing availability data correctly', function () {
            // Arrange
            $event = Event::factory()->create();
            $timeSlot = EventTimeSlot::factory()->forEvent($event)->create([
                'date' => now()->addDays(1),
                'start_time' => '09:00:00',
                'end_time' => '17:00:00',
            ]);
            $participant = EventParticipant::factory()->forEvent($event)->create();

            // Create existing availability
            ParticipantAvailability::factory()->create([
                'event_id' => $event->id,
                'participant_id' => $participant->id,
                'date' => now()->addDays(1),
                'start_time' => '10:00:00',
                'end_time' => '14:00:00',
            ]);

            // Act
            $response = $this->get("/{$event->hash}/participants/{$participant->id}/availability/edit");

            // Assert
            $response->assertStatus(200);
            $viewData = $response->original->getData();
            $existingAvailability = $viewData['existingAvailability'];

            $dateKey = now()->addDays(1)->format('Y-m-d');
            expect($existingAvailability)->toHaveKey($dateKey);
            expect($existingAvailability[$dateKey])->toContain([
                'start_time' => '10:00:00',
                'end_time' => '14:00:00',
            ]);
        });

        it('generates correct time options for dropdowns', function () {
            // Arrange
            $event = Event::factory()->create();
            $timeSlot = EventTimeSlot::factory()->forEvent($event)->create([
                'date' => now()->addDays(1),
                'start_time' => '09:00:00',
                'end_time' => '12:00:00',
            ]);
            $participant = EventParticipant::factory()->forEvent($event)->create();

            // Act
            $response = $this->get("/{$event->hash}/participants/{$participant->id}/availability/edit");

            // Assert
            $viewData = $response->original->getData();
            $timeOptions = $viewData['timeOptions'];
            $dateKey = now()->addDays(1)->format('Y-m-d');

            expect($timeOptions)->toHaveKey($dateKey);
            expect($timeOptions[$dateKey])->toHaveKey('09:00');
            expect($timeOptions[$dateKey])->toHaveKey('09:30');
            expect($timeOptions[$dateKey])->toHaveKey('10:00');
            expect($timeOptions[$dateKey])->toHaveKey('12:00');

            // Verify display format
            expect($timeOptions[$dateKey]['09:00'])->toBe('9:00 AM');
            expect($timeOptions[$dateKey]['12:00'])->toBe('12:00 PM');
        });

        it('returns 404 for invalid participant ID', function () {
            $event = Event::factory()->create();
            EventTimeSlot::factory()->forEvent($event)->create();

            $invalidParticipantId = 99999;

            $response = $this->get("/{$event->hash}/participants/{$invalidParticipantId}/availability/edit");

            $response->assertStatus(404);
        });

        it('redirects when participant does not belong to event', function () {
            // Arrange
            $event1 = Event::factory()->create();
            $event2 = Event::factory()->create();

            EventTimeSlot::factory()->forEvent($event1)->create();
            EventTimeSlot::factory()->forEvent($event2)->create();

            $participant = EventParticipant::factory()->forEvent($event2)->create();

            // Act: Try to access participant from different event
            $response = $this->get("/{$event1->hash}/participants/{$participant->id}/availability/edit");

            // Assert
            $response->assertRedirect(route('events.show', $event1->hash));
            $response->assertSessionHas('error', 'Invalid participant access. Please enter your name to continue.');
        });
    });

    describe('Updating Availability (PUT /{event:hash}/participants/{participant}/availability)', function () {
        it('updates participant availability successfully', function () {
            // Arrange
            $event = Event::factory()->create();
            $timeSlot = EventTimeSlot::factory()->forEvent($event)->create([
                'date' => now()->addDays(1),
                'start_time' => '09:00:00',
                'end_time' => '17:00:00',
            ]);
            $participant = EventParticipant::factory()->forEvent($event)->create();

            $availabilityData = [
                'participant_name' => $participant->name,
                'availability' => [
                    now()->addDays(1)->format('Y-m-d') => [
                        ['start_time' => '09:00', 'end_time' => '12:00'],
                        ['start_time' => '14:00', 'end_time' => '17:00'],
                    ],
                ],
            ];

            // Act
            $response = $this->put("/{$event->hash}/participants/{$participant->id}/availability", $availabilityData);

            // Assert
            $response->assertStatus(200);
            $response->assertViewIs('participant-edit');
            // Note: Success message is set via ->with() on view, not session flash

            // Verify availability was saved (time stored without seconds)
            $this->assertDatabaseHas('participant_availabilities', [
                'event_id' => $event->id,
                'participant_id' => $participant->id,
                'date' => now()->addDays(1)->format('Y-m-d').' 00:00:00',
                'start_time' => '09:00',
                'end_time' => '12:00',
            ]);

            $this->assertDatabaseHas('participant_availabilities', [
                'event_id' => $event->id,
                'participant_id' => $participant->id,
                'date' => now()->addDays(1)->format('Y-m-d').' 00:00:00',
                'start_time' => '14:00',
                'end_time' => '17:00',
            ]);
        });

        it('handles empty availability data', function () {
            $event = Event::factory()->create();
            $timeSlot = EventTimeSlot::factory()->forEvent($event)->create([
                'date' => now()->addDays(1),
                'start_time' => '09:00:00',
                'end_time' => '17:00:00',
            ]);
            $participant = EventParticipant::factory()->forEvent($event)->create();

            $availabilityData = [
                'participant_name' => $participant->name,
                'availability' => [],
            ];

            $response = $this->put("/{$event->hash}/participants/{$participant->id}/availability", $availabilityData);

            $response->assertStatus(200);
            $response->assertViewIs('participant-edit');

            // Verify no availability records exist
            $availabilityCount = ParticipantAvailability::where('participant_id', $participant->id)->count();
            expect($availabilityCount)->toBe(0);
        });

        it('deletes old availability before creating new ones', function () {
            // Arrange
            $event = Event::factory()->create();
            $timeSlot = EventTimeSlot::factory()->forEvent($event)->create([
                'date' => now()->addDays(1),
                'start_time' => '09:00:00',
                'end_time' => '17:00:00',
            ]);
            $participant = EventParticipant::factory()->forEvent($event)->create();

            // Create old availability
            ParticipantAvailability::factory()->create([
                'event_id' => $event->id,
                'participant_id' => $participant->id,
                'date' => now()->addDays(1),
                'start_time' => '10:00:00',
                'end_time' => '12:00:00',
            ]);

            $newAvailabilityData = [
                'participant_name' => $participant->name,
                'availability' => [
                    now()->addDays(1)->format('Y-m-d') => [
                        ['start_time' => '14:00', 'end_time' => '16:00'],
                    ],
                ],
            ];

            // Act
            $response = $this->put("/{$event->hash}/participants/{$participant->id}/availability", $newAvailabilityData);

            // Assert
            $response->assertStatus(200);

            // Should only have new availability, old one should be deleted
            $availabilities = ParticipantAvailability::where('participant_id', $participant->id)->get();
            expect($availabilities->count())->toBe(1);
            expect($availabilities->first()->start_time)->toBe('14:00');
            expect($availabilities->first()->end_time)->toBe('16:00');
        });

        it('validates time range formats through UpdateAvailabilityRequest', function () {
            $event = Event::factory()->create();
            $timeSlot = EventTimeSlot::factory()->forEvent($event)->create([
                'date' => now()->addDays(1),
                'start_time' => '09:00:00',
                'end_time' => '17:00:00',
            ]);
            $participant = EventParticipant::factory()->forEvent($event)->create();

            $invalidData = [
                'participant_name' => $participant->name,
                'availability' => [
                    now()->addDays(1)->format('Y-m-d') => [
                        ['start_time' => 'invalid-time', 'end_time' => '12:00'],
                    ],
                ],
            ];

            $response = $this->put("/{$event->hash}/participants/{$participant->id}/availability", $invalidData);

            $response->assertStatus(302);
            $response->assertSessionHasErrors();
        });

        it('prevents cross-event availability updates', function () {
            // Arrange
            $event1 = Event::factory()->create();
            $event2 = Event::factory()->create();

            EventTimeSlot::factory()->forEvent($event1)->create([
                'date' => now()->addDays(1),
                'start_time' => '09:00:00',
                'end_time' => '17:00:00',
            ]);

            $participant = EventParticipant::factory()->forEvent($event2)->create();

            $availabilityData = [
                'participant_name' => $participant->name,
                'availability' => [
                    now()->addDays(1)->format('Y-m-d') => [
                        ['start_time' => '09:00', 'end_time' => '12:00'],
                    ],
                ],
            ];

            // Act: Try to update with wrong event
            $response = $this->put("/{$event1->hash}/participants/{$participant->id}/availability", $availabilityData);

            // Assert: Should be blocked by UpdateAvailabilityRequest::authorize()
            $response->assertStatus(403);
        });
    });
});
