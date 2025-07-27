<x-layout title="{{ $event->name }} - When2Meet" assets="events">
    <x-card class="mx-auto max-w-4xl">
        <h1 class="mb-8 text-center text-3xl font-bold text-gray-900">
            {{ $event->name }}
        </h1>

        {{-- Event Time Information --}}
        <div class="mb-8">
            <h2 class="mb-4 text-xl font-semibold text-gray-800">
                Event Details
            </h2>
            @foreach ($event->timeSlots as $timeSlot)
                <div class="mb-4 rounded-lg bg-gray-50 p-4">
                    <div class="flex items-center justify-between">
                        <span class="font-medium text-gray-700">Date:</span>
                        <span class="text-gray-900">
                            {{ $timeSlot->date->format("Y-m-d") }}
                        </span>
                    </div>
                    <div class="mt-2 flex items-center justify-between">
                        <span class="font-medium text-gray-700">
                            Time (
                            <span class="timezone-label">Loading...</span>
                            ):
                        </span>
                        <span
                            class="text-gray-900 timezone-display"
                            data-utc-start="{{ $timeSlot->start_time }}"
                            data-utc-end="{{ $timeSlot->end_time }}"
                        >
                            Loading time...
                        </span>
                    </div>
                </div>
            @endforeach
        </div>

        {{-- Group Availability Visualization --}}
        @if (isset($groupAvailability) && count($groupAvailability) > 0)
            <div class="mb-8">
                <h2 class="mb-4 text-xl font-semibold text-gray-800">
                    Group Availability
                </h2>
                <div class="rounded-lg bg-gray-50 p-4">
                    <div class="space-y-2">
                        @foreach ($groupAvailability as $slot)
                            <div class="flex items-center justify-between rounded-lg bg-white p-3 shadow-sm hover:shadow-md transition-shadow">
                                {{-- Time Range --}}
                                <div class="flex flex-col">
                                    <span class="font-medium text-gray-900">
                                        {{ $slot['start_time'] }} - {{ $slot['end_time'] }}
                                    </span>
                                    <span class="text-sm text-gray-500">
                                        {{ \Carbon\Carbon::parse($slot['date'])->format('M j, Y') }}
                                    </span>
                                </div>

                                {{-- Availability Bar --}}
                                <div class="flex flex-1 items-center ml-4 mr-4">
                                    <div class="flex-1 bg-gray-200 rounded-full h-6 relative overflow-hidden">
                                        <div 
                                            class="h-full bg-gradient-to-r from-green-400 to-green-600 rounded-full transition-all duration-300"
                                            style="width: {{ $slot['availability_percentage'] }}%"
                                            title="Available participants: {{ implode(', ', $slot['available_participants']) }}"
                                        ></div>
                                        <div class="absolute inset-0 flex items-center justify-center">
                                            <span class="text-xs font-medium text-gray-700">
                                                {{ $slot['available_count'] }}/{{ $slot['total_participants'] }}
                                            </span>
                                        </div>
                                    </div>
                                </div>

                                {{-- Percentage --}}
                                <div class="text-right">
                                    <span class="text-lg font-bold text-gray-900">
                                        {{ $slot['availability_percentage'] }}%
                                    </span>
                                    @if ($slot['available_count'] > 0)
                                        <div class="text-xs text-gray-500 mt-1" title="Available: {{ implode(', ', $slot['available_participants']) }}">
                                            {{ count($slot['available_participants']) }} available
                                        </div>
                                    @endif
                                </div>
                            </div>
                        @endforeach
                    </div>
                    
                    {{-- Best Time Recommendation --}}
                    @php
                        $bestSlot = collect($groupAvailability)->sortByDesc('available_count')->first();
                    @endphp
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
                </div>
            </div>
        @endif

        {{-- Error Message --}}
        @if (session('error'))
            <div class="mb-6 rounded-lg border border-red-200 bg-red-50 p-4 text-sm text-red-600">
                {{ session('error') }}
            </div>
        @endif

        {{-- Participant Name Form --}}
        <x-participant-name-form :event="$event" />
    </x-card>
</x-layout>
