<div class="time-range-selector mb-3 rounded border border-gray-200 bg-gray-50 p-3" data-index="{{ $index }}">
    <div class="flex flex-col gap-3 lg:flex-row lg:items-center">
        {{-- Start Time Select --}}
        <div class="flex-1">
            <label class="mb-1 block text-sm font-medium text-gray-700">
                Start Time <span class="text-red-500">*</span>
            </label>
            <select 
                name="availability[{{ $date }}][{{ $index }}][start_time]"
                class="time-select start-time w-full rounded border border-gray-300 bg-white px-3 py-2 text-sm focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500"
                data-date="{{ $date }}"
                data-index="{{ $index }}"
            >
                <option value="">Select start time</option>
                @foreach ($timeOptions as $value => $label)
                    <option value="{{ $value }}" {{ $startTime === $value ? 'selected' : '' }}>
                        {{ $label }}
                    </option>
                @endforeach
            </select>
        </div>

        {{-- Arrow or "to" text --}}
        <div class="flex items-center justify-center text-gray-500">
            <span class="text-sm font-medium">to</span>
        </div>

        {{-- End Time Select --}}
        <div class="flex-1">
            <label class="mb-1 block text-sm font-medium text-gray-700">
                End Time <span class="text-red-500">*</span>
            </label>
            <select 
                name="availability[{{ $date }}][{{ $index }}][end_time]"
                class="time-select end-time w-full rounded border border-gray-300 bg-white px-3 py-2 text-sm focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500"
                data-date="{{ $date }}"
                data-index="{{ $index }}"
            >
                <option value="">Select end time</option>
                @foreach ($timeOptions as $value => $label)
                    <option value="{{ $value }}" {{ $endTime === $value ? 'selected' : '' }}>
                        {{ $label }}
                    </option>
                @endforeach
            </select>
        </div>

        {{-- Delete Button --}}
        @if ($canDelete)
            <div class="flex items-end">
                <button 
                    type="button" 
                    class="remove-time-range rounded bg-red-500 px-3 py-2 text-sm font-medium text-white hover:bg-red-600 focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2"
                    data-date="{{ $date }}"
                    data-index="{{ $index }}"
                >
                    Remove
                </button>
            </div>
        @endif
    </div>

    {{-- Error message container --}}
    <div class="error-message mt-2 hidden text-sm text-red-600"></div>
</div>