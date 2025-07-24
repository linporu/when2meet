@props(['name', 'label', 'options' => [], 'required' => false, 'selected' => null, 'placeholder' => null])

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
    <select
        id="{{ $name }}"
        name="{{ $name }}"
        class="w-full rounded-lg border border-gray-300 px-4 py-3 transition-colors outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-500"
        @if($required)
            required
        @endif
        {{ $attributes }}
    >
        @if($placeholder)
            <option value="">{{ $placeholder }}</option>
        @endif
        @foreach($options as $value => $text)
            <option
                value="{{ $value }}"
                {{ ($selected ?? old($name)) == $value ? 'selected' : '' }}
            >
                {{ $text }}
            </option>
        @endforeach
    </select>
</div>