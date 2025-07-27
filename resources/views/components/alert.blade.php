@props(['messages' => [], 'message' => null, 'type' => 'error'])

@php
    // Support both single message and array of messages
    $allMessages = [];
    if ($message) {
        $allMessages[] = $message;
    }
    if (is_array($messages) && count($messages) > 0) {
        $allMessages = array_merge($allMessages, $messages);
    }

    // Define styling based on type
    $styles = [
        'error' => 'border-red-200 bg-red-50 text-red-600',
        'success' => 'border-green-200 bg-green-50 text-green-600',
        'warning' => 'border-yellow-200 bg-yellow-50 text-yellow-600',
        'info' => 'border-blue-200 bg-blue-50 text-blue-600',
    ];
    $alertClass = $styles[$type] ?? $styles['error'];
@endphp

@if(count($allMessages) > 0)
    <div class="mb-6 rounded-lg border p-4 text-sm {{ $alertClass }}">
        @if (count($allMessages) === 1)
            {{ $allMessages[0] }}
        @else
            <ul class="space-y-1">
                @foreach ($allMessages as $message)
                    <li>{{ $message }}</li>
                @endforeach
            </ul>
        @endif
    </div>
@endif