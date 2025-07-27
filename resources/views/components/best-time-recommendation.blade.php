@props(['bestSlot'])

@if ($bestSlot && $bestSlot['available_count'] > 0)
    <div class="mt-4 p-3 bg-green-50 border border-green-200 rounded-lg">
        <div class="flex items-center">
            <div class="flex-shrink-0">
                <svg class="h-5 w-5 text-green-400" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                </svg>
            </div>
            <div class="ml-3">
                <p class="text-sm font-medium text-green-800">
                    Recommended: {{ $bestSlot['start_time'] }} - {{ $bestSlot['end_time'] }} 
                    on {{ \Carbon\Carbon::parse($bestSlot['date'])->format('M j, Y') }}
                </p>
                <p class="text-xs text-green-600">
                    {{ $bestSlot['available_count'] }} out of {{ $bestSlot['total_participants'] }} participants available
                </p>
            </div>
        </div>
    </div>
@endif