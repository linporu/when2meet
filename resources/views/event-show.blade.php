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
