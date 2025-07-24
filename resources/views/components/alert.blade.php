@props(['messages' => [], 'type' => 'error'])

@if(count($messages) > 0)
    <div
        class="mb-6 rounded-lg border border-red-200 bg-red-50 p-4 text-sm text-red-600"
    >
        <ul class="space-y-1">
            @foreach ($messages as $message)
                <li>{{ $message }}</li>
            @endforeach
        </ul>
    </div>
@endif