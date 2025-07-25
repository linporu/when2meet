<div class="time-range-list" data-date="{{ $date }}">
    {{-- Existing Time Ranges --}}
    <div class="time-ranges-container">
        @if (count($existingRanges) > 0)
            @foreach ($existingRanges as $index => $range)
                <x-time-range-selector 
                    :date="$date"
                    :time-options="$timeOptions"
                    :index="$index"
                    :start-time="$range['start_time'] ?? ''"
                    :end-time="$range['end_time'] ?? ''"
                    :can-delete="count($existingRanges) > 1"
                />
            @endforeach
        @else
            {{-- Default empty time range --}}
            <x-time-range-selector 
                :date="$date"
                :time-options="$timeOptions"
                :index="0"
                :can-delete="false"
            />
        @endif
    </div>
    
    {{-- Add Time Range Button --}}
    <div class="mt-3">
        <x-add-time-range-button />
    </div>
</div>