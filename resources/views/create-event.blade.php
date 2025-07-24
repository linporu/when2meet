<x-layout title="Create New Event - When2Meet" assets="events">
    <x-card class="mx-auto max-w-2xl">
        <h1 class="mb-8 text-center text-3xl font-bold text-gray-900">
            Create New Event
        </h1>

        <x-alert :messages="$errors->all()" />

        <form id="event-form" method="POST" action="/">
            @csrf

            <x-forms.input 
                name="event_name" 
                label="Event Name" 
                placeholder="Enter event name" 
                :required="true"
            />

            <x-forms.input 
                name="date" 
                type="date" 
                label="Date" 
                :required="true"
            />

            <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                <x-forms.input 
                    name="start_time" 
                    type="time" 
                    label="Start Time" 
                    value="09:00"
                    :required="true"
                />

                <x-forms.input 
                    name="end_time" 
                    type="time" 
                    label="End Time" 
                    value="17:00"
                    :required="true"
                />
            </div>

            <x-forms.select 
                name="timezone" 
                label="Timezone" 
                placeholder="Select timezone"
                :options="[
                    'Asia/Taipei' => 'Taipei (UTC+8)',
                    'Asia/Tokyo' => 'Tokyo (UTC+9)',
                    'Asia/Shanghai' => 'Shanghai (UTC+8)',
                    'Asia/Hong_Kong' => 'Hong Kong (UTC+8)',
                    'Asia/Singapore' => 'Singapore (UTC+8)',
                    'UTC' => 'UTC (UTC+0)',
                    'America/New_York' => 'New York (UTC-5)',
                    'America/Los_Angeles' => 'Los Angeles (UTC-8)',
                    'Europe/London' => 'London (UTC+0)',
                ]"
                :required="true"
            />

            <div class="mb-6">
                <x-button type="submit" class="w-full">
                    Create Event
                </x-button>
            </div>
        </form>
    </x-card>
</x-layout>