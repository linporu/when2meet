@props(["groupAvailability" => []])

{{-- Group Availability Visualization --}}
@if (isset($groupAvailability) && count($groupAvailability) > 0)
    <div class="mb-8">
        <h2 class="mb-4 text-xl font-semibold text-gray-800">
            Group Availability
        </h2>
        <div class="rounded-lg bg-gray-50 p-4">
            {{-- Best Time Recommendation --}}
            @php
                $bestSlot = collect($groupAvailability)
                    ->sortByDesc("available_count")
                    ->first();
            @endphp

            <div class="mb-4">
                <x-best-time-recommendation :bestSlot="$bestSlot" />
            </div>

            <div class="space-y-2">
                @foreach ($groupAvailability as $slot)
                    <x-availability-slot :availabilitySlot="$slot" />
                @endforeach
            </div>
        </div>
    </div>
@endif
