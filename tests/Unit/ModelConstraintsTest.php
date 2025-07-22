<?php

use App\Models\Event;
use App\Models\EventParticipant;
use Illuminate\Database\QueryException;

describe('Model Constraints', function () {
    describe('Event Constraints', function () {
        it('enforces unique hash constraint', function () {
            $hash = 'test1234';
            Event::factory()->withCustomHash($hash)->create();

            expect(fn () => Event::factory()->withCustomHash($hash)->create())
                ->toThrow(QueryException::class);
        });

        it('allows events with different hashes', function () {
            $event1 = Event::factory()->withCustomHash('hash0001')->create();
            $event2 = Event::factory()->withCustomHash('hash0002')->create();

            expect($event1->hash)->toBe('hash0001');
            expect($event2->hash)->toBe('hash0002');
        });

        it('accepts long names in SQLite (no length constraint enforced)', function () {
            $longName = str_repeat('a', 129); // 129 chars

            $event = Event::factory()->withName($longName)->create();
            expect($event->name)->toBe($longName);
        });

        it('allows names within length limit', function () {
            $validName = str_repeat('a', 128); // Exactly 128 chars
            $event = Event::factory()->withName($validName)->create();

            expect($event->name)->toBe($validName);
        });
    });

    describe('EventParticipant Constraints', function () {
        it('enforces unique participant name per event', function () {
            $event = Event::factory()->create();
            $participantName = 'John Doe';

            EventParticipant::factory()->forEvent($event)->withName($participantName)->create();

            expect(fn () => EventParticipant::factory()->forEvent($event)->withName($participantName)->create())
                ->toThrow(QueryException::class);
        });

        it('allows same participant name in different events', function () {
            $event1 = Event::factory()->create();
            $event2 = Event::factory()->create();
            $participantName = 'John Doe';

            $participant1 = EventParticipant::factory()->forEvent($event1)->withName($participantName)->create();
            $participant2 = EventParticipant::factory()->forEvent($event2)->withName($participantName)->create();

            expect($participant1->name)->toBe($participantName);
            expect($participant2->name)->toBe($participantName);
            expect($participant1->event_id)->not->toBe($participant2->event_id);
        });

        it('accepts long participant names in SQLite (no length constraint enforced)', function () {
            $longName = str_repeat('a', 65); // 65 chars

            $participant = EventParticipant::factory()->withName($longName)->create();
            expect($participant->name)->toBe($longName);
        });

        it('allows participant names within length limit', function () {
            $validName = str_repeat('a', 64); // Exactly 64 chars
            $participant = EventParticipant::factory()->withName($validName)->create();

            expect($participant->name)->toBe($validName);
        });
    });

    describe('Foreign Key Constraints', function () {
        it('prevents creating EventParticipant with non-existent event', function () {
            expect(fn () => EventParticipant::factory()->make(['event_id' => 99999])->save())
                ->toThrow(QueryException::class);
        });

        it('cascades deletion from Event to EventParticipant', function () {
            $event = Event::factory()->create();
            $participant = EventParticipant::factory()->forEvent($event)->create();

            expect(EventParticipant::count())->toBe(1);

            $event->delete();

            expect(EventParticipant::count())->toBe(0);
        });

        it('cascades deletion from EventParticipant to AvailableDatetime', function () {
            $participant = EventParticipant::factory()->create();
            $availableDatetime = \App\Models\AvailableDatetime::factory()->forParticipant($participant)->create();

            expect(\App\Models\AvailableDatetime::count())->toBe(1);

            $participant->delete();

            expect(\App\Models\AvailableDatetime::count())->toBe(0);
        });

        it('cascades deletion from Event through participants to AvailableDatetime', function () {
            $event = Event::factory()->create();
            $participant = EventParticipant::factory()->forEvent($event)->create();
            $availableDatetime = \App\Models\AvailableDatetime::factory()->forParticipant($participant)->create();

            expect(\App\Models\AvailableDatetime::count())->toBe(1);
            expect(EventParticipant::count())->toBe(1);

            $event->delete();

            expect(\App\Models\AvailableDatetime::count())->toBe(0);
            expect(EventParticipant::count())->toBe(0);
        });
    });

    describe('Data Integrity', function () {
        it('maintains referential integrity across all models', function () {
            $event = Event::factory()->create();
            $participant = EventParticipant::factory()->forEvent($event)->create();
            $availableDatetime = \App\Models\AvailableDatetime::factory()->forParticipant($participant)->create();

            expect($availableDatetime->event_id)->toBe($event->id);
            expect($availableDatetime->participant_id)->toBe($participant->id);
            expect($participant->event_id)->toBe($event->id);
        });
    });
});
