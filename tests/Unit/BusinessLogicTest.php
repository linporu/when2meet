<?php

use App\Models\Event;
use App\Models\EventParticipant;
use App\Models\ParticipantAvailability;

describe('Business Logic and Edge Cases', function () {
    describe('EventParticipant Password Logic', function () {
        it('can set and verify password', function () {
            $participant = EventParticipant::factory()->withPassword('secret123')->create();

            expect($participant->password)->not->toBe('secret123'); // Should be hashed
            expect($participant->checkPassword('secret123'))->toBeTrue();
            expect($participant->checkPassword('wrong'))->toBeFalse();
        });

        it('handles participants without password', function () {
            $participant = EventParticipant::factory()->withoutPassword()->create();

            expect($participant->password)->toBeNull();
            expect($participant->checkPassword('anything'))->toBeTrue(); // No password required
        });

        it('hides password in serialization', function () {
            $participant = EventParticipant::factory()->withPassword('secret123')->create();
            $array = $participant->toArray();

            expect($array)->not->toHaveKey('password');
        });
    });

    describe('AvailableDatetime Business Logic', function () {
        it('can filter by date range', function () {
            $event = Event::factory()->create();
            $participant = EventParticipant::factory()->forEvent($event)->create();

            $early = ParticipantAvailability::factory()->forParticipant($participant)->onDate('2024-01-01')->create();
            $middle = ParticipantAvailability::factory()->forParticipant($participant)->onDate('2024-01-15')->create();
            $late = ParticipantAvailability::factory()->forParticipant($participant)->onDate('2024-01-31')->create();

            $filtered = ParticipantAvailability::forDateRange('2024-01-10', '2024-01-20')->get();

            expect($filtered)->toHaveCount(1);
            expect($filtered->first()->id)->toBe($middle->id);
        });

        it('can filter by time range', function () {
            $event = Event::factory()->create();
            $participant = EventParticipant::factory()->forEvent($event)->create();

            $early = ParticipantAvailability::factory()->forParticipant($participant)->timeRange('09:00:00', '10:00:00')->create();
            $middle = ParticipantAvailability::factory()->forParticipant($participant)->timeRange('14:00:00', '15:00:00')->create();
            $late = ParticipantAvailability::factory()->forParticipant($participant)->timeRange('19:00:00', '20:00:00')->create();

            $filtered = ParticipantAvailability::forTimeRange('13:00:00', '16:00:00')->get();

            expect($filtered)->toHaveCount(1);
            expect($filtered->first()->id)->toBe($middle->id);
        });

        it('can detect overlapping time slots', function () {
            $event = Event::factory()->create();
            $participant = EventParticipant::factory()->forEvent($event)->create();

            $slot1 = ParticipantAvailability::factory()
                ->forParticipant($participant)
                ->onDate('2024-01-15')
                ->timeRange('14:00:00', '16:00:00')
                ->create();

            $slot2 = ParticipantAvailability::factory()
                ->forParticipant($participant)
                ->onDate('2024-01-15')
                ->timeRange('15:00:00', '17:00:00')
                ->create();

            expect($slot1->isOverlapping($slot2))->toBeTrue();
            expect($slot2->isOverlapping($slot1))->toBeTrue();
        });

        it('detects non-overlapping time slots', function () {
            $event = Event::factory()->create();
            $participant = EventParticipant::factory()->forEvent($event)->create();

            $slot1 = ParticipantAvailability::factory()
                ->forParticipant($participant)
                ->onDate('2024-01-15')
                ->timeRange('14:00:00', '16:00:00')
                ->create();

            $slot2 = ParticipantAvailability::factory()
                ->forParticipant($participant)
                ->onDate('2024-01-15')
                ->timeRange('17:00:00', '19:00:00')
                ->create();

            expect($slot1->isOverlapping($slot2))->toBeFalse();
            expect($slot2->isOverlapping($slot1))->toBeFalse();
        });

        it('detects overlapping on different dates returns false', function () {
            $event = Event::factory()->create();
            $participant = EventParticipant::factory()->forEvent($event)->create();

            $slot1 = ParticipantAvailability::factory()
                ->forParticipant($participant)
                ->onDate('2024-01-15')
                ->timeRange('14:00:00', '16:00:00')
                ->create();

            $slot2 = ParticipantAvailability::factory()
                ->forParticipant($participant)
                ->onDate('2024-01-16')
                ->timeRange('15:00:00', '17:00:00')
                ->create();

            expect($slot1->isOverlapping($slot2))->toBeFalse();
        });
    });

    describe('Edge Cases', function () {
        it('handles empty event with no participants', function () {
            $event = Event::factory()->create();

            expect($event->participants)->toHaveCount(0);
            expect($event->availableDatetimes)->toHaveCount(0);
        });

        it('handles participant with no available times', function () {
            $participant = EventParticipant::factory()->create();

            expect($participant->availableDatetimes)->toHaveCount(0);
        });

        it('handles minimum and maximum string lengths', function () {
            // Test minimum valid lengths (1 character)
            $event = Event::factory()->withName('A')->create();
            $participant = EventParticipant::factory()->forEvent($event)->withName('B')->create();

            expect($event->name)->toBe('A');
            expect($participant->name)->toBe('B');

            // Test maximum valid lengths
            $maxEventName = str_repeat('X', 128);
            $maxParticipantName = str_repeat('Y', 64);

            $eventMax = Event::factory()->withName($maxEventName)->create();
            $participantMax = EventParticipant::factory()->forEvent($eventMax)->withName($maxParticipantName)->create();

            expect($eventMax->name)->toBe($maxEventName);
            expect($participantMax->name)->toBe($maxParticipantName);
        });

        it('handles time boundary conditions', function () {
            $event = Event::factory()->create();
            $participant = EventParticipant::factory()->forEvent($event)->create();

            // Test time slots at day boundaries
            $midnightStart = ParticipantAvailability::factory()
                ->forParticipant($participant)
                ->timeRange('00:00:00', '01:00:00')
                ->create();

            $almostMidnight = ParticipantAvailability::factory()
                ->forParticipant($participant)
                ->timeRange('23:00:00', '23:59:59')
                ->create();

            expect($midnightStart->start_time)->toBe('00:00:00');
            expect($almostMidnight->end_time)->toBe('23:59:59');
        });

        it('handles multiple participants with same availability', function () {
            $event = Event::factory()->create();
            $participant1 = EventParticipant::factory()->forEvent($event)->withName('Alice')->create();
            $participant2 = EventParticipant::factory()->forEvent($event)->withName('Bob')->create();

            // Both available at same time
            $date = '2024-01-15';
            $timeRange = ['14:00:00', '16:00:00'];

            $availability1 = ParticipantAvailability::factory()
                ->forParticipant($participant1)
                ->onDate($date)
                ->timeRange($timeRange[0], $timeRange[1])
                ->create();

            $availability2 = ParticipantAvailability::factory()
                ->forParticipant($participant2)
                ->onDate($date)
                ->timeRange($timeRange[0], $timeRange[1])
                ->create();

            // Verify both participants have same availability details
            expect($availability1->event_id)->toBe($event->id);
            expect($availability2->event_id)->toBe($event->id);
            expect($availability1->date->format('Y-m-d'))->toBe($date);
            expect($availability2->date->format('Y-m-d'))->toBe($date);
            expect($availability1->start_time)->toBe($timeRange[0]);
            expect($availability1->end_time)->toBe($timeRange[1]);
            expect($availability2->start_time)->toBe($timeRange[0]);
            expect($availability2->end_time)->toBe($timeRange[1]);
        });
    });
});
