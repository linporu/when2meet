<?php

namespace Database\Factories;

use App\Models\Event;
use App\Models\EventParticipant;
use App\Models\AvailableDatetime;
use Illuminate\Database\Eloquent\Factories\Factory;

class AvailableDatetimeFactory extends Factory
{
    protected $model = AvailableDatetime::class;

    public function definition(): array
    {
        // Generate reasonable start time for personal availability (8 AM - 8 PM)
        $startHour = $this->faker->numberBetween(8, 20);
        $startMinute = $this->faker->randomElement([0, 15, 30, 45]);
        $startTime = sprintf('%02d:%02d:00', $startHour, $startMinute);
        
        // Generate reasonable duration for meetings/appointments (30 minutes to 4 hours)
        $durationMinutes = $this->faker->numberBetween(30, 240);
        
        // Calculate end time
        $endTimestamp = strtotime("1970-01-01 $startTime") + ($durationMinutes * 60);
        
        // Ensure we don't exceed the day boundary
        if ($endTimestamp >= strtotime('1970-01-02 00:00:00')) {
            $endTimestamp = strtotime('1970-01-01 23:59:59');
        }
        
        $endTime = date('H:i:s', $endTimestamp);

        return [
            'event_id' => Event::factory(),
            'participant_id' => EventParticipant::factory(),
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

    public function forParticipant(EventParticipant $participant): static
    {
        return $this->state(fn (array $attributes) => [
            'participant_id' => $participant->id,
            'event_id' => $participant->event_id,
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