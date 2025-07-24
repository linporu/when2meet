<?php

use App\Models\Event;
use App\Models\EventTimeSlot;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('can display event page with valid hash', function () {
    $event = Event::factory()->create([
        'name' => 'Test Meeting',
    ]);

    EventTimeSlot::factory()->create([
        'event_id' => $event->id,
        'date' => '2025-08-01',
        'start_time' => '01:00:00',  // UTC time (台北時間 09:00 = UTC 01:00)
        'end_time' => '09:00:00',   // UTC time (台北時間 17:00 = UTC 09:00)
    ]);

    $response = $this->get('/'.$event->hash);

    $response->assertStatus(200);
    $response->assertSee('Test Meeting');
    $response->assertSee('2025-08-01');
    $response->assertSee('data-utc-start="01:00:00"', false);
    $response->assertSee('data-utc-end="09:00:00"', false);
    $response->assertSee('timezone-display', false);
    $response->assertSee('Loading time...');
    $response->assertSee('Enter your name');
});

test('returns 404 for non-existent event hash', function () {
    $response = $this->get('/nonexistent');

    $response->assertStatus(404);
});

test('event page contains participant join form', function () {
    $event = Event::factory()->create();

    $response = $this->get('/'.$event->hash);

    $response->assertStatus(200);
    $response->assertSee('name="participant_name"', false);
    $response->assertSee('Join Event');
});

test('event page displays timezone conversion elements', function () {
    $event = Event::factory()->create([
        'name' => 'Timezone Test Event',
    ]);

    EventTimeSlot::factory()->create([
        'event_id' => $event->id,
        'date' => '2025-08-15',
        'start_time' => '02:30:00',  // UTC time
        'end_time' => '10:30:00',   // UTC time
    ]);

    $response = $this->get('/'.$event->hash);

    $response->assertStatus(200);

    // 驗證日期格式正確（不含時間）
    $response->assertSee('2025-08-15');
    $response->assertDontSee('00:00:00');

    // 驗證時區轉換所需的 data attributes
    $response->assertSee('data-utc-start="02:30:00"', false);
    $response->assertSee('data-utc-end="10:30:00"', false);

    // 驗證前端時區轉換的容器元素
    $response->assertSee('class="text-gray-900 timezone-display"', false);

    // 驗證新的時區標籤結構
    $response->assertSee('class="timezone-label"', false);
    $response->assertSee('Time (');
    $response->assertSee('):');

    // 驗證 placeholder 文字（分別檢查時區和時間的 placeholder）
    $response->assertSee('Loading...');
    $response->assertSee('Loading time...');
});
