<?php

namespace App\View\Components;

use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

class TimeRangeList extends Component
{
    public string $date;

    public array $timeOptions;

    public array $existingRanges;

    /**
     * Create a new component instance.
     */
    public function __construct(
        string $date,
        array $timeOptions,
        array $existingRanges = []
    ) {
        $this->date = $date;
        $this->timeOptions = $timeOptions;
        $this->existingRanges = $existingRanges;
    }

    /**
     * Get the view / contents that represent the component.
     */
    public function render(): View|Closure|string
    {
        return view('components.time-range-list');
    }
}
