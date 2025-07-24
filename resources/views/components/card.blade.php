@props(['padding' => 'p-8'])

<div {{ $attributes->merge(['class' => "rounded-lg bg-white shadow-md {$padding}"]) }}>
    {{ $slot }}
</div>