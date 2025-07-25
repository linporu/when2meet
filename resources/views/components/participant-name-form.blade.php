<div class="border-t pt-8">
    <h2 class="mb-4 text-xl font-semibold text-gray-800">
        Join This Event
    </h2>
    <p class="mb-6 text-gray-600">
        Enter your name to participate in this event and mark your availability.
    </p>

    <form method="POST" action="{{ route('events.enterName', $event->hash) }}">
        @csrf

        {{-- Participant Name Input --}}
        <div class="mb-6">
            <x-forms.input
                name="participant_name"
                label="Your Name"
                placeholder="Enter your name"
                :required="true"
            />
        </div>

        {{-- Submit Button --}}
        <div class="mb-6">
            <x-button type="submit" class="w-full">
                Continue to Set Availability
            </x-button>
        </div>
    </form>
</div>