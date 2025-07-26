<?php

use App\Models\Event;
use App\Models\EventParticipant;
use App\Models\EventTimeSlot;
use App\Models\ParticipantAvailability;

describe('End-to-End User Journey', function () {
    describe('Complete Event Creation to Participation Flow', function () {
        it('completes full event creation to participation workflow', function () {
            // Step 1: Create Event (Homepage Flow)
            $eventData = [
                'event_name' => 'Team Sprint Planning Meeting 🚀',
                'date' => today()->addWeeks(2)->format('Y-m-d'),
                'start_time' => '09:00',
                'end_time' => '17:00',
                'timezone' => 'Asia/Taipei',
            ];

            $response = $this->post('/', $eventData);
            $response->assertStatus(302);

            // Verify event was created
            $event = Event::where('name', $eventData['event_name'])->first();
            expect($event)->not->toBeNull();

            // Verify event has correct time slot
            $timeSlot = $event->timeSlots()->first();
            expect($timeSlot)->not->toBeNull();
            expect($timeSlot->date->format('Y-m-d'))->toBe($eventData['date']);

            // Should redirect to event page
            $response->assertRedirect("/{$event->hash}");

            // Step 2: Access Event Page
            $response = $this->get("/{$event->hash}");
            $response->assertStatus(200);
            $response->assertViewIs('event-show');
            $response->assertSee($event->name);
            $response->assertSee('Join This Event');

            // Step 3: Set Participant Name (First User)
            $firstParticipantName = 'Alice Johnson';
            $response = $this->post("/{$event->hash}/participants", [
                'participant_name' => $firstParticipantName,
            ]);
            $response->assertStatus(302);

            // Verify participant was created
            $firstParticipant = EventParticipant::where('event_id', $event->id)
                ->where('name', $firstParticipantName)
                ->first();
            expect($firstParticipant)->not->toBeNull();

            // Should redirect to availability edit
            $response->assertRedirect(route('participants.availability.edit', [
                'event' => $event->hash,
                'participant' => $firstParticipant->id,
            ]));

            // Step 4: Access Availability Edit Page
            $response = $this->get("/{$event->hash}/participants/{$firstParticipant->id}/availability/edit");
            $response->assertStatus(200);
            $response->assertViewIs('participant-edit');
            $response->assertSee($firstParticipant->name);
            $response->assertSee('Set your availability for this event');

            // Step 5: Submit Availability (First User)
            $firstAvailabilityData = [
                'participant_name' => $firstParticipantName,
                'availability' => [
                    $eventData['date'] => [
                        ['start_time' => '09:00', 'end_time' => '12:00'],
                        ['start_time' => '14:00', 'end_time' => '17:00'],
                    ],
                ],
            ];

            $response = $this->put("/{$event->hash}/participants/{$firstParticipant->id}/availability", $firstAvailabilityData);
            $response->assertStatus(200);
            $response->assertViewIs('participant-edit');

            // Verify first user's availability was saved
            $this->assertDatabaseHas('participant_availabilities', [
                'event_id' => $event->id,
                'participant_id' => $firstParticipant->id,
                'date' => $eventData['date'].' 00:00:00',
                'start_time' => '09:00',
                'end_time' => '12:00',
            ]);

            // Step 6: Second User Joins Event (Different Browser Simulation)
            $response = $this->get("/{$event->hash}");
            $response->assertStatus(200);

            $secondParticipantName = 'Bob Smith';
            $response = $this->post("/{$event->hash}/participants", [
                'participant_name' => $secondParticipantName,
            ]);
            $response->assertStatus(302);

            $secondParticipant = EventParticipant::where('event_id', $event->id)
                ->where('name', $secondParticipantName)
                ->first();
            expect($secondParticipant)->not->toBeNull();

            // Step 7: Second User Sets Availability (Overlapping Times)
            $secondAvailabilityData = [
                'participant_name' => $secondParticipantName,
                'availability' => [
                    $eventData['date'] => [
                        ['start_time' => '10:00', 'end_time' => '15:00'],
                    ],
                ],
            ];

            $response = $this->put("/{$event->hash}/participants/{$secondParticipant->id}/availability", $secondAvailabilityData);
            $response->assertStatus(200);

            // Verify second user's availability was saved
            $this->assertDatabaseHas('participant_availabilities', [
                'event_id' => $event->id,
                'participant_id' => $secondParticipant->id,
                'date' => $eventData['date'].' 00:00:00',
                'start_time' => '10:00',
                'end_time' => '15:00',
            ]);

            // Step 8: Verify Final State - Event Has Multiple Participants
            $finalEvent = Event::with('participants', 'participantAvailabilities')->find($event->id);
            expect($finalEvent->participants->count())->toBe(2);
            expect($finalEvent->participantAvailabilities->count())->toBe(3); // 2 from first user + 1 from second user

            // Verify both participants can access their own edit pages
            $response1 = $this->get("/{$event->hash}/participants/{$firstParticipant->id}/availability/edit");
            $response1->assertStatus(200);

            $response2 = $this->get("/{$event->hash}/participants/{$secondParticipant->id}/availability/edit");
            $response2->assertStatus(200);

            // Step 9: Verify Event Page Is Accessible
            $response = $this->get("/{$event->hash}");
            $response->assertStatus(200);
            $response->assertSee($event->name);
        });

        it('handles participant updating their availability multiple times', function () {
            // Setup: Create event and participant
            $event = Event::factory()->create([
                'name' => 'Iterative Planning Session',
            ]);

            $timeSlot = EventTimeSlot::factory()->forEvent($event)->create([
                'date' => today()->addDays(5),
                'start_time' => '09:00:00',
                'end_time' => '18:00:00',
            ]);

            $participant = EventParticipant::factory()->forEvent($event)->create([
                'name' => 'Iterative User',
            ]);

            // Initial availability submission
            $initialAvailability = [
                'participant_name' => $participant->name,
                'availability' => [
                    $timeSlot->date->format('Y-m-d') => [
                        ['start_time' => '09:00', 'end_time' => '12:00'],
                    ],
                ],
            ];

            $response = $this->put("/{$event->hash}/participants/{$participant->id}/availability", $initialAvailability);
            $response->assertStatus(200);

            // Verify initial availability
            $initialCount = ParticipantAvailability::where('participant_id', $participant->id)->count();
            expect($initialCount)->toBe(1);

            // Update availability (should replace previous)
            $updatedAvailability = [
                'participant_name' => $participant->name,
                'availability' => [
                    $timeSlot->date->format('Y-m-d') => [
                        ['start_time' => '10:00', 'end_time' => '14:00'],
                        ['start_time' => '15:00', 'end_time' => '17:00'],
                    ],
                ],
            ];

            $response = $this->put("/{$event->hash}/participants/{$participant->id}/availability", $updatedAvailability);
            $response->assertStatus(200);

            // Verify old availability was replaced
            $finalCount = ParticipantAvailability::where('participant_id', $participant->id)->count();
            expect($finalCount)->toBe(2);

            // Verify specific times
            $this->assertDatabaseHas('participant_availabilities', [
                'participant_id' => $participant->id,
                'start_time' => '10:00',
                'end_time' => '14:00',
            ]);

            $this->assertDatabaseMissing('participant_availabilities', [
                'participant_id' => $participant->id,
                'start_time' => '09:00',
                'end_time' => '12:00',
            ]);
        });

        it('supports multiple participants with complex availability patterns', function () {
            // Create event with longer duration
            $event = Event::factory()->create([
                'name' => 'Multi-Day Workshop',
            ]);

            $timeSlot = EventTimeSlot::factory()->forEvent($event)->create([
                'date' => today()->addWeeks(1),
                'start_time' => '08:00:00',
                'end_time' => '20:00:00',
            ]);

            // Create multiple participants with varying availability
            $participants = [
                ['name' => 'Early Bird', 'times' => [['08:00', '12:00']]],
                ['name' => 'Night Owl', 'times' => [['16:00', '20:00']]],
                ['name' => 'Flexible Worker', 'times' => [['09:00', '11:00'], ['14:00', '16:00'], ['18:00', '19:00']]],
                ['name' => 'Part Timer', 'times' => [['10:00', '15:00']]],
                ['name' => 'Meeting Marathoner', 'times' => [['08:00', '20:00']]],
            ];

            $createdParticipants = [];

            foreach ($participants as $participantData) {
                // Join event
                $response = $this->post("/{$event->hash}/participants", [
                    'participant_name' => $participantData['name'],
                ]);
                $response->assertStatus(302);

                $participant = EventParticipant::where('event_id', $event->id)
                    ->where('name', $participantData['name'])
                    ->first();

                $createdParticipants[] = $participant;

                // Set availability
                $availabilityData = [
                    'participant_name' => $participantData['name'],
                    'availability' => [
                        $timeSlot->date->format('Y-m-d') => array_map(function ($time) {
                            return ['start_time' => $time[0], 'end_time' => $time[1]];
                        }, $participantData['times']),
                    ],
                ];

                $response = $this->put("/{$event->hash}/participants/{$participant->id}/availability", $availabilityData);
                $response->assertStatus(200);
            }

            // Verify all participants were created
            expect(count($createdParticipants))->toBe(5);

            // Verify complex availability patterns were saved
            $totalAvailabilities = ParticipantAvailability::where('event_id', $event->id)->count();
            expect($totalAvailabilities)->toBe(7); // Sum of all time slots: 1+1+3+1+1 = 7

            // Verify specific patterns
            $earlyBird = $createdParticipants[0];
            $this->assertDatabaseHas('participant_availabilities', [
                'participant_id' => $earlyBird->id,
                'start_time' => '08:00',
                'end_time' => '12:00',
            ]);

            $flexibleWorker = $createdParticipants[2];
            $flexibleAvailabilities = ParticipantAvailability::where('participant_id', $flexibleWorker->id)->count();
            expect($flexibleAvailabilities)->toBe(3);
        });
    });

    describe('User Experience and Error Recovery Flows', function () {
        it('gracefully handles participant trying to access wrong event', function () {
            // Create two separate events
            $event1 = Event::factory()->create(['name' => 'Event A']);
            $event2 = Event::factory()->create(['name' => 'Event B']);

            // Create participant in event2
            $participant = EventParticipant::factory()->forEvent($event2)->create([
                'name' => 'Confused User',
            ]);

            // Try to access event1 with event2 participant
            $response = $this->get("/{$event1->hash}/participants/{$participant->id}/availability/edit");

            // Should redirect to event1 homepage with helpful error
            $response->assertRedirect(route('events.show', $event1->hash));
            $response->assertSessionHas('error', 'Invalid participant access. Please enter your name to continue.');

            // Follow redirect and verify user can start fresh
            $response = $this->get("/{$event1->hash}");
            $response->assertStatus(200);
            $response->assertSee('Join This Event');
        });

        it('handles participant name conflicts gracefully', function () {
            $event = Event::factory()->create(['name' => 'Name Conflict Test']);

            // First user sets name
            $commonName = 'John Smith';
            $response = $this->post("/{$event->hash}/participants", [
                'participant_name' => $commonName,
            ]);
            $response->assertStatus(302);

            $firstParticipant = EventParticipant::where('event_id', $event->id)
                ->where('name', $commonName)
                ->first();

            // Second user tries same name (should reuse existing participant)
            $response = $this->post("/{$event->hash}/participants", [
                'participant_name' => $commonName,
            ]);
            $response->assertStatus(302);

            // Should redirect to same participant
            $response->assertRedirect(route('participants.availability.edit', [
                'event' => $event->hash,
                'participant' => $firstParticipant->id,
            ]));

            // Verify only one participant exists
            $participantCount = EventParticipant::where('event_id', $event->id)
                ->where('name', $commonName)
                ->count();
            expect($participantCount)->toBe(1);
        });

        it('validates complete user journey with invalid inputs and recovery', function () {
            // Step 1: Try to create event with invalid data
            $invalidEventData = [
                'event_name' => '',  // Missing name
                'date' => '2020-01-01',  // Past date
                'start_time' => '25:00',  // Invalid time
                'end_time' => '09:00',  // End before start
                'timezone' => 'Invalid/Timezone',
            ];

            $response = $this->post('/', $invalidEventData);
            $response->assertStatus(302);
            $response->assertSessionHasErrors(['event_name', 'date', 'start_time', 'end_time', 'timezone']);

            // Step 2: User corrects and creates valid event
            $validEventData = [
                'event_name' => 'Recovery Test Event',
                'date' => today()->addDays(7)->format('Y-m-d'),
                'start_time' => '09:00',
                'end_time' => '17:00',
                'timezone' => 'Asia/Taipei',
            ];

            $response = $this->post('/', $validEventData);
            $response->assertStatus(302);

            $event = Event::where('name', $validEventData['event_name'])->first();
            expect($event)->not->toBeNull();

            // Step 3: Try to join with invalid participant name
            $response = $this->post("/{$event->hash}/participants", [
                'participant_name' => '',  // Empty name
            ]);
            $response->assertStatus(302);
            $response->assertSessionHasErrors(['participant_name']);

            // Step 4: User provides valid name
            $participantName = 'Recovery User';
            $response = $this->post("/{$event->hash}/participants", [
                'participant_name' => $participantName,
            ]);
            $response->assertStatus(302);

            $participant = EventParticipant::where('event_id', $event->id)
                ->where('name', $participantName)
                ->first();
            expect($participant)->not->toBeNull();

            // Step 5: Try to submit invalid availability
            $invalidAvailability = [
                'participant_name' => $participantName,
                'availability' => [
                    $validEventData['date'] => [
                        ['start_time' => 'invalid-time', 'end_time' => '12:00'],
                    ],
                ],
            ];

            $response = $this->put("/{$event->hash}/participants/{$participant->id}/availability", $invalidAvailability);
            $response->assertStatus(302);
            $response->assertSessionHasErrors();

            // Step 6: User submits valid availability
            $validAvailability = [
                'participant_name' => $participantName,
                'availability' => [
                    $validEventData['date'] => [
                        ['start_time' => '10:00', 'end_time' => '16:00'],
                    ],
                ],
            ];

            $response = $this->put("/{$event->hash}/participants/{$participant->id}/availability", $validAvailability);
            $response->assertStatus(200);

            // Verify successful completion
            $this->assertDatabaseHas('participant_availabilities', [
                'participant_id' => $participant->id,
                'start_time' => '10:00',
                'end_time' => '16:00',
            ]);
        });
    });

    describe('Performance and Scalability Edge Cases', function () {
        it('handles event with many participants efficiently', function () {
            $event = Event::factory()->create([
                'name' => 'Large Scale Meeting',
            ]);

            $timeSlot = EventTimeSlot::factory()->forEvent($event)->create([
                'date' => today()->addDays(10),
                'start_time' => '09:00:00',
                'end_time' => '17:00:00',
            ]);

            // Simulate 20 participants joining
            $participantNames = [];
            for ($i = 1; $i <= 20; $i++) {
                $participantNames[] = "Participant {$i}";
            }

            foreach ($participantNames as $name) {
                $response = $this->post("/{$event->hash}/participants", [
                    'participant_name' => $name,
                ]);
                $response->assertStatus(302);

                $participant = EventParticipant::where('event_id', $event->id)
                    ->where('name', $name)
                    ->first();

                // Each participant sets different availability
                $availabilityData = [
                    'participant_name' => $name,
                    'availability' => [
                        $timeSlot->date->format('Y-m-d') => [
                            ['start_time' => '09:00', 'end_time' => '12:00'],
                            ['start_time' => '14:00', 'end_time' => '17:00'],
                        ],
                    ],
                ];

                $response = $this->put("/{$event->hash}/participants/{$participant->id}/availability", $availabilityData);
                $response->assertStatus(200);
            }

            // Verify all participants and availability records exist
            $finalParticipantCount = EventParticipant::where('event_id', $event->id)->count();
            expect($finalParticipantCount)->toBe(20);

            $totalAvailabilities = ParticipantAvailability::where('event_id', $event->id)->count();
            expect($totalAvailabilities)->toBe(40); // 20 participants × 2 time slots each

            // Verify event page still loads efficiently
            $response = $this->get("/{$event->hash}");
            $response->assertStatus(200);
        });
    });
});
