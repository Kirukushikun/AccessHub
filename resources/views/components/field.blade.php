@props(['label' => null, 'for' => null, 'hint' => null, 'error' => null])

<div {{ $attributes->merge(['class' => 'space-y-1']) }}>
    @if ($label)
        <label @if($for) for="{{ $for }}" @endif class="block text-sm font-medium text-gray-700">{{ $label }}</label>
    @endif
    {{ $slot }}
    @if ($hint && ! $error)
        <p class="text-xs text-gray-400">{{ $hint }}</p>
    @endif
    @if ($error)
        <p class="text-xs text-red-600">{{ $error }}</p>
    @endif
</div>
