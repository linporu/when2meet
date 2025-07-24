@props(['type' => 'button', 'variant' => 'primary'])

@php
    $baseClasses = 'rounded-lg px-6 py-3 font-semibold transition-colors outline-none focus:ring-2 focus:ring-offset-2';
    $variantClasses = [
        'primary' => 'bg-blue-600 text-white hover:bg-blue-700 focus:ring-blue-500',
        'secondary' => 'bg-gray-600 text-white hover:bg-gray-700 focus:ring-gray-500',
    ];
    $classes = $baseClasses . ' ' . ($variantClasses[$variant] ?? $variantClasses['primary']);
@endphp

<button
    type="{{ $type }}"
    class="{{ $classes }}"
    {{ $attributes }}
>
    {{ $slot }}
</button>