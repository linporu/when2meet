@props(['participantName' => ''])

<div class="border-t pt-8">
    <h2 class="mb-4 text-xl font-semibold text-gray-800">Join Event & Mark Your Availability</h2>
    
    <form id="availability-form" method="POST" action="{{ route('events.join', $event->hash) }}">
        @csrf
        
        {{-- Participant Name Input --}}
        <div class="mb-6">
            <x-forms.input
                name="participant_name"
                label="Your Name"
                placeholder="Enter your name"
                :value="$participantName"
                :required="true"
            />
        </div>

        {{-- Availability Section --}}
        <div class="mb-6">
            <h3 class="mb-4 text-lg font-medium text-gray-700">Select Your Available Times</h3>
            
            @foreach ($event->timeSlots as $timeSlot)
                <div class="mb-6 rounded-lg border border-gray-200 p-4">
                    <h4 class="mb-3 font-medium text-gray-800">
                        {{ $timeSlot->date->format('l, F j, Y') }}
                        <span class="text-sm text-gray-600">
                            ({{ $timeSlot->start_time }} - {{ $timeSlot->end_time }})
                        </span>
                    </h4>
                    
                    <x-time-range-list 
                        :date="$timeSlot->date->format('Y-m-d')"
                        :time-options="$timeOptions[$timeSlot->date->format('Y-m-d')] ?? []"
                    />
                </div>
            @endforeach
        </div>

        {{-- Submit Button --}}
        <div class="mb-6">
            <x-button type="submit" class="w-full">
                Join Event & Save Availability
            </x-button>
        </div>
    </form>
</div>