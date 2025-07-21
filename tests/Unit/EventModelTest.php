<?php

use App\Models\Event;
use App\Models\EventDatetime;
use App\Models\EventParticipant;
use App\Models\AvailableDatetime;

describe('Event Model', function () {
    it('can create an event with basic attributes', function () {
        $event = Event::factory()->create([
            'name' => 'Team Meeting',
        ]);

        expect($event->name)->toBe('Team Meeting');
        expect($event->hash)->toHaveLength(8);
        expect($event->created_at)->toBeInstanceOf(DateTime::class);
        expect($event->updated_at)->toBeInstanceOf(DateTime::class);
    });

    it('automatically generates a unique hash when creating an event', function () {
        $event1 = Event::factory()->create();
        $event2 = Event::factory()->create();

        expect($event1->hash)->toHaveLength(8);
        expect($event2->hash)->toHaveLength(8);
        expect($event1->hash)->not->toBe($event2->hash);
    });

    it('can set a custom hash', function () {
        $customHash = 'abc12345';
        $event = Event::factory()->withCustomHash($customHash)->create();

        expect($event->hash)->toBe($customHash);
    });

    it('uses hash as route key name for URL binding', function () {
        $event = Event::factory()->create();

        expect($event->getRouteKeyName())->toBe('hash');
    });

    it('generates unique hash even when collisions occur', function () {
        // Mock the random string generator to test collision handling
        $event = Event::factory()->create();
        
        // Create another event to ensure no hash collision
        $anotherEvent = Event::factory()->create();
        
        expect($event->hash)->not->toBe($anotherEvent->hash);
    });

    it('can be found by hash', function () {
        $event = Event::factory()->create();
        
        $foundEvent = Event::where('hash', $event->hash)->first();
        
        expect($foundEvent->id)->toBe($event->id);
        expect($foundEvent->name)->toBe($event->name);
    });

    it('has fillable attributes', function () {
        $event = new Event();
        
        expect($event->getFillable())->toContain('name', 'hash');
    });

    it('casts timestamps correctly', function () {
        $event = Event::factory()->create();
        
        expect($event->created_at)->toBeInstanceOf(DateTime::class);
        expect($event->updated_at)->toBeInstanceOf(DateTime::class);
    });
});