<?php

use App\Models\Event;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('event generates unique hash on creation', function () {
    $event = Event::create(['name' => '測試活動']);

    expect($event->hash)->not->toBeNull();
    expect(strlen($event->hash))->toBe(8);
});

test('event hash is unique across multiple events', function () {
    $event1 = Event::create(['name' => '活動一']);
    $event2 = Event::create(['name' => '活動二']);

    expect($event1->hash)->not->toBe($event2->hash);
});

test('event uses hash as route key', function () {
    $event = Event::create(['name' => '測試活動']);

    expect($event->getRouteKeyName())->toBe('hash');
});
