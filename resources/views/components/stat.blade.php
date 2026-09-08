@props(['label', 'value', 'hint' => null, 'tone' => 'default'])

@php
    $valueTone = [
        'default' => 'text-gray-900',
        'warn' => 'text-amber-600',
        'danger' => 'text-red-600',
    ][$tone] ?? 'text-gray-900';
@endphp

<div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm">
    <p class="text-sm text-gray-500">{{ $label }}</p>
    <p class="mt-1 text-2xl font-semibold {{ $valueTone }}">{{ $value }}</p>
    @if ($hint)
        <p class="mt-1 text-xs text-gray-400">{{ $hint }}</p>
    @endif
</div>
