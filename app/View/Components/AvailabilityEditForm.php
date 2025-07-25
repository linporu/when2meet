<?php

namespace App\View\Components;

use App\Models\Event;
use App\Models\EventParticipant;
use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

class AvailabilityEditForm extends Component
{
    public Event $event;

    public EventParticipant $participant;

    public array $existingAvailability;

    protected int $timeOptionsInterval = 1800; // 1800 seconds = 30 minutes

    /**
     * Create a new component instance.
     */
    public function __construct(Event $event, EventParticipant $participant, array $existingAvailability = [])
    {
        $this->event = $event;
        $this->participant = $participant;
        $this->existingAvailability = $existingAvailability;
    }

    /**
     * Get time options in fixed intervals for the event.
     */
    public function getTimeOptions(): array
    {
        $options = [];

        foreach ($this->event->timeSlots as $timeSlot) {
            $start = strtotime($timeSlot->start_time);
            $end = strtotime($timeSlot->end_time);

            $dateKey = $timeSlot->date->format('Y-m-d');

            if (! isset($options[$dateKey])) {
                $options[$dateKey] = [];
            }

            // Generate time options with intervals
            for ($time = $start; $time < $end; $time += $this->timeOptionsInterval) {
                $timeString = date('H:i', $time);
                $options[$dateKey][$timeString] = date('g:i A', $time);
            }

            // Add end time as final option
            $endTimeString = date('H:i', $end);
            $options[$dateKey][$endTimeString] = date('g:i A', $end);
        }

        return $options;
    }

    /**
     * Get the view / contents that represent the component.
     */
    public function render(): View|Closure|string
    {
        return view('components.availability-edit-form', [
            'timeOptions' => $this->getTimeOptions(),
        ]);
    }
}
