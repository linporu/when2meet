@props(['availabilitySlot'])

<div class="flex items-center justify-between rounded-lg bg-white p-3 shadow-sm hover:shadow-md transition-shadow">
    {{-- Time Range --}}
    <div class="flex flex-col">
        <span class="font-medium text-gray-900">
            {{ $availabilitySlot['start_time'] }} - {{ $availabilitySlot['end_time'] }}
        </span>
        <span class="text-sm text-gray-500">
            {{ \Carbon\Carbon::parse($availabilitySlot['date'])->format('M j, Y') }}
        </span>
    </div>

    {{-- Availability Bar --}}
    <div class="flex flex-1 items-center ml-4 mr-4">
        <div class="flex-1 bg-gray-200 rounded-full h-6 relative overflow-hidden">
            <div 
                class="h-full bg-gradient-to-r from-green-400 to-green-600 rounded-full transition-all duration-300"
                style="width: {{ $availabilitySlot['availability_percentage'] }}%"
                title="Available participants: {{ implode(', ', $availabilitySlot['available_participants']) }}"
            ></div>
            <div class="absolute inset-0 flex items-center justify-center">
                <span class="text-xs font-medium text-gray-700">
                    {{ $availabilitySlot['available_count'] }}/{{ $availabilitySlot['total_participants'] }}
                </span>
            </div>
        </div>
    </div>

    {{-- Percentage --}}
    <div class="text-right">
        <span class="text-lg font-bold text-gray-900">
            {{ $availabilitySlot['availability_percentage'] }}%
        </span>
        @if ($availabilitySlot['available_count'] > 0)
            <div class="text-xs text-gray-500 mt-1" title="Available: {{ implode(', ', $availabilitySlot['available_participants']) }}">
                {{ count($availabilitySlot['available_participants']) }} available
            </div>
        @endif
    </div>
</div>