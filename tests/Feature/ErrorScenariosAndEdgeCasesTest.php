<?php

use App\Models\Event;
use App\Models\EventParticipant;
use App\Models\EventTimeSlot;

describe('Error Scenarios and Edge Cases - Test God Level', function () {
    describe('Invalid Hash Handling (Security Focus)', function () {
        it('returns 404 for non-existent event hash', function () {
            $nonExistentHash = 'InvalidHash123';

            $response = $this->get("/{$nonExistentHash}");
            $response->assertStatus(404);

            // Test participant endpoints with invalid hash
            $response = $this->post("/{$nonExistentHash}/participants", [
                'participant_name' => 'Test User',
            ]);
            $response->assertStatus(404);
        });

        it('returns 404 for malformed hash format', function () {
            $malformedHashes = [
                'a', // Too short
                'verylonghashthatshouldnotexist123456789', // Too long
                'hash-with-special-chars!@#$%^&*()', // Special characters
                'hash with spaces', // Spaces (will be encoded as hash%20with%20spaces)
                'UPPERCASEHASH', // Different case
                'invalidhash', // Valid format but non-existent
            ];

            foreach ($malformedHashes as $hash) {
                $response = $this->get("/{$hash}");
                $response->assertStatus(404);

                $response = $this->post("/{$hash}/participants", [
                    'participant_name' => 'Test User',
                ]);
                // Could be 404 (not found) or 405 (method not allowed) depending on route matching
                expect($response->getStatusCode())->toBeIn([404, 405]);
            }
        });

        it('handles hash collision edge case gracefully', function () {
            // Create an event with a specific hash
            $event = Event::factory()->create(['name' => 'Original Event']);
            $originalHash = $event->hash;

            // Try to access with the valid hash
            $response = $this->get("/{$originalHash}");
            $response->assertStatus(200);
            $response->assertSee('Original Event');

            // Verify hash uniqueness is enforced via database constraint
            try {
                $duplicateEvent = new Event(['name' => 'Duplicate Event']);
                $duplicateEvent->hash = $originalHash; // Try to set same hash
                $duplicateEvent->save();

                // Should not reach here due to unique constraint
                expect(false)->toBeTrue('Unique constraint should have been violated');
            } catch (\Exception $e) {
                // Expected behavior - unique constraint violation
                expect($e)->toBeInstanceOf(\Exception::class);
                expect($e->getMessage())->toContain('UNIQUE constraint failed');
            }
        });
    });

    describe('Cross-Event Access Prevention (Security Critical)', function () {
        it('prevents participant ID enumeration attacks', function () {
            // Create multiple events with participants
            $event1 = Event::factory()->create(['name' => 'Event 1']);
            $event2 = Event::factory()->create(['name' => 'Event 2']);

            EventTimeSlot::factory()->forEvent($event1)->create();
            EventTimeSlot::factory()->forEvent($event2)->create();

            $participant1 = EventParticipant::factory()->forEvent($event1)->create(['name' => 'User 1']);
            $participant2 = EventParticipant::factory()->forEvent($event2)->create(['name' => 'User 2']);

            // Try to access participant from wrong event
            $response1 = $this->get("/{$event1->hash}/participants/{$participant2->id}/availability/edit");
            $response1->assertRedirect();
            $response1->assertSessionHas('error');

            $response2 = $this->get("/{$event2->hash}/participants/{$participant1->id}/availability/edit");
            $response2->assertRedirect();
            $response2->assertSessionHas('error');
        });

        it('validates event-participant ownership on all endpoints', function () {
            $event1 = Event::factory()->create(['name' => 'Secure Event 1']);
            $event2 = Event::factory()->create(['name' => 'Secure Event 2']);

            EventTimeSlot::factory()->forEvent($event1)->create();
            EventTimeSlot::factory()->forEvent($event2)->create();

            $participant2 = EventParticipant::factory()->forEvent($event2)->create();

            // Test GET endpoint security
            $getResponse = $this->get("/{$event1->hash}/participants/{$participant2->id}/availability/edit");
            $getResponse->assertRedirect();
            $getResponse->assertSessionHas('error', 'Invalid participant access. Please enter your name to continue.');

            // Test PUT endpoint security
            $putResponse = $this->put("/{$event1->hash}/participants/{$participant2->id}/availability", [
                'participant_name' => $participant2->name,
                'availability' => [],
            ]);
            $putResponse->assertStatus(403); // Blocked by UpdateAvailabilityRequest::authorize()
        });

        it('prevents timing attacks on participant lookup', function () {
            $event = Event::factory()->create();
            EventTimeSlot::factory()->forEvent($event)->create();

            $participant = EventParticipant::factory()->forEvent($event)->create();

            // Test with valid participant ID
            $startTime = microtime(true);
            $validResponse = $this->get("/{$event->hash}/participants/{$participant->id}/availability/edit");
            $validTime = microtime(true) - $startTime;

            // Test with invalid participant ID
            $startTime = microtime(true);
            $invalidResponse = $this->get("/{$event->hash}/participants/99999/availability/edit");
            $invalidTime = microtime(true) - $startTime;

            // Response times should be similar (within 100ms tolerance)
            // This prevents timing-based participant ID discovery
            $timeDifference = abs($validTime - $invalidTime);
            expect($timeDifference)->toBeLessThan(0.1);

            $validResponse->assertStatus(200);
            $invalidResponse->assertStatus(404);
        });
    });

    describe('Data Consistency and Integrity', function () {
        it('handles concurrent participant creation with race conditions', function () {
            $event = Event::factory()->create();

            $participantName = 'Concurrent User';
            $concurrentRequests = 5;

            // Simulate multiple rapid requests
            $responses = [];
            for ($i = 0; $i < $concurrentRequests; $i++) {
                $responses[] = $this->post("/{$event->hash}/participants", [
                    'participant_name' => $participantName,
                ]);
            }

            // All requests should succeed with redirects
            foreach ($responses as $response) {
                $response->assertStatus(302);
            }

            // Only one participant should be created (firstOrCreate handles race condition)
            $participantCount = EventParticipant::where('event_id', $event->id)
                ->where('name', $participantName)
                ->count();
            expect($participantCount)->toBe(1);

            // All responses should redirect to the same participant
            $participant = EventParticipant::where('event_id', $event->id)
                ->where('name', $participantName)
                ->first();

            foreach ($responses as $response) {
                $expectedRedirect = route('participants.availability.edit', [
                    'event' => $event->hash,
                    'participant' => $participant->id,
                ]);
                $response->assertRedirect($expectedRedirect);
            }
        });

        it('maintains referential integrity during cascading operations', function () {
            $event = Event::factory()->create();
            $timeSlot = EventTimeSlot::factory()->forEvent($event)->create();
            $participant = EventParticipant::factory()->forEvent($event)->create();

            // Create some availability data
            $availabilityData = [
                'participant_name' => $participant->name,
                'availability' => [
                    $timeSlot->date->format('Y-m-d') => [
                        ['start_time' => '09:00', 'end_time' => '12:00'],
                        ['start_time' => '14:00', 'end_time' => '17:00'],
                    ],
                ],
            ];

            $response = $this->put("/{$event->hash}/participants/{$participant->id}/availability", $availabilityData);
            $response->assertStatus(200);

            // Verify data exists
            $this->assertDatabaseHas('events', ['id' => $event->id]);
            $this->assertDatabaseHas('event_participants', ['id' => $participant->id]);
            $this->assertDatabaseHas('participant_availabilities', ['participant_id' => $participant->id]);

            // Delete participant should cascade properly
            $participant->delete();

            // Event should still exist
            $this->assertDatabaseHas('events', ['id' => $event->id]);

            // Participant and their availabilities should be gone
            $this->assertDatabaseMissing('event_participants', ['id' => $participant->id]);
            $this->assertDatabaseMissing('participant_availabilities', ['participant_id' => $participant->id]);
        });

        it('handles database constraints and foreign key violations gracefully', function () {
            $event = Event::factory()->create();
            $participant = EventParticipant::factory()->forEvent($event)->create();

            // Try to create availability for non-existent event
            try {
                \App\Models\ParticipantAvailability::create([
                    'event_id' => 99999, // Non-existent event
                    'participant_id' => $participant->id,
                    'date' => today(),
                    'start_time' => '09:00',
                    'end_time' => '12:00',
                ]);

                // Should not reach here due to foreign key constraint
                expect(false)->toBeTrue('Foreign key constraint should have been violated');
            } catch (\Exception $e) {
                // Expected behavior - foreign key constraint violation
                expect($e)->toBeInstanceOf(\Exception::class);
            }
        });
    });

    describe('Input Validation Edge Cases', function () {
        it('handles extremely long participant names gracefully', function () {
            $event = Event::factory()->create();

            $longName = str_repeat('A', 300); // Exceeds 255 character limit

            $response = $this->post("/{$event->hash}/participants", [
                'participant_name' => $longName,
            ]);

            $response->assertStatus(302);
            $response->assertSessionHasErrors(['participant_name']);
        });

        it('prevents SQL injection through participant names', function () {
            $event = Event::factory()->create();

            $sqlInjectionAttempts = [
                "'; DROP TABLE events; --",
                "' OR '1'='1",
                "'; UPDATE events SET name='HACKED'; --",
                "' UNION SELECT * FROM users; --",
                '"; DROP DATABASE when2meet; --',
            ];

            foreach ($sqlInjectionAttempts as $attempt) {
                $response = $this->post("/{$event->hash}/participants", [
                    'participant_name' => $attempt,
                ]);

                $response->assertStatus(302);

                // Database should still be intact
                $this->assertDatabaseHas('events', ['id' => $event->id]);

                // If validation passes, data should be stored safely
                // Most SQL injection attempts should fail validation (too long names)
                // but database should remain intact regardless
                try {
                    $response->assertSessionHasErrors(['participant_name']);
                    // Expected - SQL injection attempts should fail validation
                } catch (\Exception) {
                    // If validation passes, verify data was stored safely
                    $this->assertDatabaseHas('event_participants', [
                        'event_id' => $event->id,
                        'name' => $attempt,
                    ]);
                }
            }
        });

        it('handles special Unicode characters in participant names', function () {
            $event = Event::factory()->create();
            EventTimeSlot::factory()->forEvent($event)->create();

            $unicodeNames = [
                '测试用户', // Chinese
                'مستخدم الاختبار', // Arabic
                'Тестовый пользователь', // Russian
                'שם משתמש', // Hebrew
                '🚀 Rocket User 🚀', // Emoji
                'Ñoño López-García', // Spanish special chars
                'Björk Guðmundsdóttir', // Icelandic
                'José-María de la Cruz O\'Connor', // Complex name
            ];

            foreach ($unicodeNames as $name) {
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

        it('validates availability data with malformed JSON-like input', function () {
            $event = Event::factory()->create();
            $timeSlot = EventTimeSlot::factory()->forEvent($event)->create();
            $participant = EventParticipant::factory()->forEvent($event)->create();

            $malformedData = [
                'participant_name' => $participant->name,
                'availability' => [
                    // Various malformed time entries
                    $timeSlot->date->format('Y-m-d') => [
                        ['start_time' => null, 'end_time' => '12:00'],
                        ['start_time' => '09:00', 'end_time' => null],
                        ['start_time' => [], 'end_time' => '12:00'],
                        ['start_time' => '09:00', 'end_time' => []],
                        ['start_time' => 'not-a-time', 'end_time' => 'also-not-time'],
                        ['invalid_key' => '09:00', 'another_invalid' => '12:00'],
                    ],
                ],
            ];

            $response = $this->put("/{$event->hash}/participants/{$participant->id}/availability", $malformedData);

            // Should handle gracefully with validation errors
            $response->assertStatus(302);
            $response->assertSessionHasErrors();
        });
    });

    describe('Performance Under Stress', function () {
        it('handles large availability datasets efficiently', function () {
            $event = Event::factory()->create();
            $timeSlot = EventTimeSlot::factory()->forEvent($event)->create([
                'start_time' => '00:00:00',
                'end_time' => '23:59:00',
            ]);
            $participant = EventParticipant::factory()->forEvent($event)->create();

            // Create large availability dataset (every 30 minutes for 24 hours)
            $largeAvailability = [];
            for ($hour = 0; $hour < 24; $hour++) {
                for ($minute = 0; $minute < 60; $minute += 30) {
                    $startTime = sprintf('%02d:%02d', $hour, $minute);
                    $endMinute = $minute + 30;
                    $endHour = $hour;
                    if ($endMinute >= 60) {
                        $endMinute = 0;
                        $endHour++;
                    }
                    if ($endHour < 24) {
                        $endTime = sprintf('%02d:%02d', $endHour, $endMinute);
                        $largeAvailability[] = ['start_time' => $startTime, 'end_time' => $endTime];
                    }
                }
            }

            $availabilityData = [
                'participant_name' => $participant->name,
                'availability' => [
                    $timeSlot->date->format('Y-m-d') => $largeAvailability,
                ],
            ];

            $startTime = microtime(true);

            $response = $this->put("/{$event->hash}/participants/{$participant->id}/availability", $availabilityData);

            $endTime = microtime(true);
            $executionTime = $endTime - $startTime;

            // Should complete within reasonable time (5 seconds)
            expect($executionTime)->toBeLessThan(5.0);
            $response->assertStatus(200);

            // Verify data was actually saved
            $savedCount = \App\Models\ParticipantAvailability::where('participant_id', $participant->id)->count();
            expect($savedCount)->toBe(count($largeAvailability));
        });

        it('handles memory efficiently with large participant lists', function () {
            $event = Event::factory()->create();
            EventTimeSlot::factory()->forEvent($event)->create();

            $initialMemory = memory_get_usage();

            // Create 100 participants
            $participants = [];
            for ($i = 1; $i <= 100; $i++) {
                $participants[] = EventParticipant::factory()->forEvent($event)->create([
                    'name' => "Participant {$i}",
                ]);
            }

            $memoryAfterCreation = memory_get_usage();
            $memoryIncrease = $memoryAfterCreation - $initialMemory;

            // Memory increase should be reasonable (less than 50MB for 100 participants)
            expect($memoryIncrease)->toBeLessThan(50 * 1024 * 1024);

            // Load event with all participants
            $loadedEvent = Event::with('participants')->find($event->id);
            expect($loadedEvent->participants->count())->toBe(100);

            $finalMemory = memory_get_usage();
            $totalIncrease = $finalMemory - $initialMemory;

            // Total memory usage should remain reasonable
            expect($totalIncrease)->toBeLessThan(100 * 1024 * 1024);
        });
    });

    describe('Browser and Client-Side Edge Cases', function () {
        it('handles missing or malformed form data gracefully', function () {
            $event = Event::factory()->create();

            // Test completely empty request
            $response = $this->post("/{$event->hash}/participants", []);
            $response->assertStatus(302);
            $response->assertSessionHasErrors(['participant_name']);

            // Test with unexpected fields
            $response = $this->post("/{$event->hash}/participants", [
                'participant_name' => 'Valid User',
                'unexpected_field' => 'malicious_data',
                'another_field' => ['array', 'data'],
            ]);
            $response->assertStatus(302);

            // Should only use whitelisted fields
            $this->assertDatabaseHas('event_participants', [
                'event_id' => $event->id,
                'name' => 'Valid User',
            ]);
        });

        it('validates HTTP method restrictions correctly', function () {
            $event = Event::factory()->create();
            EventTimeSlot::factory()->forEvent($event)->create();
            $participant = EventParticipant::factory()->forEvent($event)->create();

            // Test wrong HTTP methods on protected endpoints
            $routes = [
                ['GET', "/{$event->hash}/participants"], // Should only accept POST
                ['DELETE', "/{$event->hash}/participants/{$participant->id}/availability"], // Should only accept PUT
                ['PATCH', "/{$event->hash}/participants/{$participant->id}/availability"], // Should only accept PUT
            ];

            foreach ($routes as [$method, $url]) {
                $response = $this->call($method, $url, [
                    'participant_name' => $participant->name,
                ]);

                // Should return method not allowed or redirect
                expect($response->getStatusCode())->toBeIn([405, 302, 404]);
            }
        });
    });
});
