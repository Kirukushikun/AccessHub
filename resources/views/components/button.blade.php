@props(['variant' => 'primary', 'href' => null, 'type' => 'button'])

@php
    $base = 'inline-flex items-center justify-center gap-1.5 rounded-md px-3 py-2 text-sm font-medium transition disabled:opacity-50';
    $variants = [
        'primary' => 'bg-gray-900 text-white hover:bg-gray-700',
        'secondary' => 'border border-gray-300 bg-white text-gray-700 hover:bg-gray-50',
        'danger' => 'bg-red-600 text-white hover:bg-red-500',
        'ghost' => 'text-gray-600 hover:bg-gray-100 hover:text-gray-900',
    ];
    $classes = $base.' '.($variants[$variant] ?? $variants['primary']);
@endphp

@if ($href)
    <a href="{{ $href }}" {{ $attributes->merge(['class' => $classes]) }}>{{ $slot }}</a>
@else
    <button type="{{ $type }}" {{ $attributes->merge(['class' => $classes]) }}>{{ $slot }}</button>
@endif
