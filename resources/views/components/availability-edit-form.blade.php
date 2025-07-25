<div>
    <h1 class="mb-8 text-center text-3xl font-bold text-gray-900">
        {{ $event->name }}
    </h1>

    {{-- Participant Information --}}
    <div class="mb-8 rounded-lg bg-blue-50 p-4">
        <h2 class="mb-2 text-lg font-semibold text-blue-900">
            Welcome, {{ $participant->name }}!
        </h2>
        <p class="text-blue-700">
            Set your availability for this event. You can modify your times anytime by returning to this page.
        </p>
    </div>

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

    {{-- Availability Form --}}
    <div class="border-t pt-8">
        <h2 class="mb-4 text-xl font-semibold text-gray-800">
            Your Availability
        </h2>

        <form
            id="availability-form"
            method="POST"
            action="{{ route("participants.availability.update", [$event->hash, $participant->id]) }}"
        >
            @csrf
            @method('PUT')

            {{-- Hidden participant name to maintain compatibility with existing join method --}}
            <input type="hidden" name="participant_name" value="{{ $participant->name }}" />

            {{-- Availability Section --}}
            <div class="mb-6">
                <h3 class="mb-4 text-lg font-medium text-gray-700">
                    Select Your Available Times
                </h3>

                @foreach ($event->timeSlots as $timeSlot)
                    <div class="mb-6 rounded-lg border border-gray-200 p-4">
                        <h4 class="mb-3 font-medium text-gray-800">
                            {{ $timeSlot->date->format("Y-m-d") }}
                            <span class="text-sm text-gray-600">
                                (
                                <span
                                    class="timezone-display"
                                    data-utc-start="{{ $timeSlot->start_time }}"
                                    data-utc-end="{{ $timeSlot->end_time }}"
                                >
                                    Loading time...
                                </span>
                                <span class="timezone-label">Loading...</span>
                                )
                            </span>
                        </h4>

                        <x-time-range-list
                            :date="$timeSlot->date->format('Y-m-d')"
                            :time-options="$timeOptions[$timeSlot->date->format('Y-m-d')] ?? []"
                            :existing-ranges="$existingAvailability[$timeSlot->date->format('Y-m-d')] ?? []"
                        />
                    </div>
                @endforeach
            </div>

            {{-- Submit Button --}}
            <div class="mb-6">
                <x-button type="submit" class="w-full">
                    Save Availability
                </x-button>
            </div>
        </form>
    </div>
</div>