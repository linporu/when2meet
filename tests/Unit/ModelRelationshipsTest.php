<?php

use App\Models\Event;
use App\Models\EventParticipant;
use App\Models\EventTimeSlot;
use App\Models\ParticipantAvailability;

describe('Model Relationships', function () {
    describe('Event Relationships', function () {
        it('has many event datetimes', function () {
            $event = Event::factory()->create();
            $eventDatetime = EventTimeSlot::factory()->forEvent($event)->create();

            expect($event->eventDatetimes)->toHaveCount(1);
            expect($event->eventDatetimes->first()->id)->toBe($eventDatetime->id);
        });

        it('has many participants', function () {
            $event = Event::factory()->create();
            $participant = EventParticipant::factory()->forEvent($event)->create();

            expect($event->participants)->toHaveCount(1);
            expect($event->participants->first()->id)->toBe($participant->id);
        });

        it('has many available datetimes through participants', function () {
            $event = Event::factory()->create();
            $participant = EventParticipant::factory()->forEvent($event)->create();
            $availableDatetime = ParticipantAvailability::factory()->forParticipant($participant)->create();

            expect($event->availableDatetimes)->toHaveCount(1);
            expect($event->availableDatetimes->first()->id)->toBe($availableDatetime->id);
        });
    });

    describe('EventDatetime Relationships', function () {
        it('belongs to an event', function () {
            $event = Event::factory()->create();
            $eventDatetime = EventTimeSlot::factory()->forEvent($event)->create();

            expect($eventDatetime->event->id)->toBe($event->id);
        });
    });

    describe('EventParticipant Relationships', function () {
        it('belongs to an event', function () {
            $event = Event::factory()->create();
            $participant = EventParticipant::factory()->forEvent($event)->create();

            expect($participant->event->id)->toBe($event->id);
        });

        it('has many available datetimes', function () {
            $participant = EventParticipant::factory()->create();
            $availableDatetime = ParticipantAvailability::factory()->forParticipant($participant)->create();

            expect($participant->availableDatetimes)->toHaveCount(1);
            expect($participant->availableDatetimes->first()->id)->toBe($availableDatetime->id);
        });
    });

    describe('AvailableDatetime Relationships', function () {
        it('belongs to an event', function () {
            $event = Event::factory()->create();
            $participant = EventParticipant::factory()->forEvent($event)->create();
            $availableDatetime = ParticipantAvailability::factory()->forParticipant($participant)->create();

            expect($availableDatetime->event->id)->toBe($event->id);
        });

        it('belongs to a participant', function () {
            $participant = EventParticipant::factory()->create();
            $availableDatetime = ParticipantAvailability::factory()->forParticipant($participant)->create();

            expect($availableDatetime->participant->id)->toBe($participant->id);
        });
    });

    describe('Complex Relationship Scenarios', function () {
        it('can access participants through event and their available times', function () {
            $event = Event::factory()->create();
            $participant1 = EventParticipant::factory()->forEvent($event)->withName('John')->create();
            $participant2 = EventParticipant::factory()->forEvent($event)->withName('Jane')->create();

            $availableTime1 = ParticipantAvailability::factory()->forParticipant($participant1)->create();
            $availableTime2 = ParticipantAvailability::factory()->forParticipant($participant2)->create();

            expect($event->participants)->toHaveCount(2);
            expect($event->availableDatetimes)->toHaveCount(2);

            $participantNames = $event->participants->pluck('name')->toArray();
            expect($participantNames)->toContain('John', 'Jane');
        });

        it('maintains referential integrity through relationships', function () {
            $event = Event::factory()->create();
            $participant = EventParticipant::factory()->forEvent($event)->create();
            $availableDatetime = ParticipantAvailability::factory()->forParticipant($participant)->create();

            // All foreign keys should be consistent
            expect($availableDatetime->event_id)->toBe($event->id);
            expect($availableDatetime->participant_id)->toBe($participant->id);
            expect($participant->event_id)->toBe($event->id);

            // Relationships should be properly linked
            expect($availableDatetime->event->hash)->toBe($event->hash);
            expect($availableDatetime->participant->name)->toBe($participant->name);
        });
    });
});
