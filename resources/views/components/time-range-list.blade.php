<div class="time-range-list" data-date="{{ $date }}">
    {{-- Container for JavaScript-rendered time ranges --}}
    <div class="time-ranges-container" 
         data-existing-ranges="{{ json_encode($existingRanges) }}"
         data-time-options="{{ json_encode($timeOptions) }}">
        {{-- JavaScript will render all time range selectors here --}}
    </div>
    
    {{-- Add Time Range Button --}}
    <div class="mt-3">
        <x-add-time-range-button />
    </div>
</div>