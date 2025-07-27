<x-layout title="{{ $event->name }} - When2Meet" assets="events">
    <x-card class="mx-auto max-w-4xl">
        <h1 class="mb-8 text-center text-3xl font-bold text-gray-900">
            {{ $event->name }}
        </h1>

        <x-event-details :event="$event" />

        <x-alert :message="session('error')" />

        <x-participant-name-form :event="$event" />

        <x-group-availability :groupAvailability="$groupAvailability ?? []" />
    </x-card>
</x-layout>
