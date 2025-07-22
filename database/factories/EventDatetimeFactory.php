<?php

namespace Database\Factories;

use App\Models\Event;
use App\Models\EventDatetime;
use Illuminate\Database\Eloquent\Factories\Factory;

class EventDatetimeFactory extends Factory
{
    protected $model = EventDatetime::class;

    public function definition(): array
    {
        // Generate reasonable start time for events (8 AM - 6 PM)
        $startHour = $this->faker->numberBetween(8, 18);
        $startMinute = $this->faker->randomElement([0, 30]);
        $startTime = sprintf('%02d:%02d:00', $startHour, $startMinute);

        // Generate reasonable duration for events (1 to 8 hours)
        $durationMinutes = $this->faker->numberBetween(60, 480);

        // Calculate end time
        $endTimestamp = strtotime("1970-01-01 $startTime") + ($durationMinutes * 60);

        // Ensure we don't exceed the day boundary
        if ($endTimestamp >= strtotime('1970-01-02 00:00:00')) {
            $endTimestamp = strtotime('1970-01-01 23:59:59');
        }

        $endTime = date('H:i:s', $endTimestamp);

        return [
            'event_id' => Event::factory(),
            'date' => $this->faker->dateTimeBetween('now', '+2 weeks')->format('Y-m-d'),
            'start_time' => $startTime,
            'end_time' => $endTime,
        ];
    }

    public function forEvent(Event $event): static
    {
        return $this->state(fn (array $attributes) => [
            'event_id' => $event->id,
        ]);
    }

    public function onDate(string $date): static
    {
        return $this->state(fn (array $attributes) => [
            'date' => $date,
        ]);
    }

    public function timeRange(string $startTime, string $endTime): static
    {
        return $this->state(fn (array $attributes) => [
            'start_time' => $startTime,
            'end_time' => $endTime,
        ]);
    }
}
