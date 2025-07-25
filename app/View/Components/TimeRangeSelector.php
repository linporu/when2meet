<?php

namespace App\View\Components;

use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

class TimeRangeSelector extends Component
{
    public string $date;

    public array $timeOptions;

    public int $index;

    public string $startTime;

    public string $endTime;

    public bool $canDelete;

    /**
     * Create a new component instance.
     */
    public function __construct(
        string $date,
        array $timeOptions,
        int $index,
        string $startTime = '',
        string $endTime = '',
        bool $canDelete = true
    ) {
        $this->date = $date;
        $this->timeOptions = $timeOptions;
        $this->index = $index;
        $this->startTime = $startTime;
        $this->endTime = $endTime;
        $this->canDelete = $canDelete;
    }

    /**
     * Get the view / contents that represent the component.
     */
    public function render(): View|Closure|string
    {
        return view('components.time-range-selector');
    }
}
