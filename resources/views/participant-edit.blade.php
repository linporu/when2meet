<x-layout title="{{ $event->name }} - Edit Availability - When2Meet" assets="events">
    <x-card class="mx-auto max-w-4xl">
        {{-- Success Message --}}
        @if (session('success'))
            <div class="mb-6 rounded-lg border border-green-200 bg-green-50 p-4 text-sm text-green-600">
                {{ session('success') }}
            </div>
        @endif

        <x-availability-edit-form 
            :event="$event" 
            :participant="$participant" 
            :existing-availability="$existingAvailability" 
        />

        <x-group-availability :groupAvailability="$groupAvailability ?? []" />
    </x-card>
</x-layout>