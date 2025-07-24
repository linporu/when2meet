@props(['name', 'label', 'type' => 'text', 'placeholder' => null, 'required' => false, 'value' => null])

<div class="mb-6">
    <label
        for="{{ $name }}"
        class="mb-2 block text-sm font-medium text-gray-700"
    >
        {{ $label }}
        @if($required)
            <span class="text-red-500">*</span>
        @endif
    </label>
    <input
        type="{{ $type }}"
        id="{{ $name }}"
        name="{{ $name }}"
        class="w-full rounded-lg border border-gray-300 px-4 py-3 transition-colors outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-500"
        value="{{ $value ?? old($name) }}"
        @if($placeholder)
            placeholder="{{ $placeholder }}"
        @endif
        @if($required)
            required
        @endif
        {{ $attributes }}
    />
</div>