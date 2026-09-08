@php
    $map = [
        'success' => 'border-green-200 bg-green-50 text-green-800',
        'error' => 'border-red-200 bg-red-50 text-red-800',
        'info' => 'border-amber-200 bg-amber-50 text-amber-800',
    ];
@endphp

@foreach (['success', 'error', 'info'] as $type)
    @if (session($type))
        <div x-data="{ show: true }" x-show="show" x-cloak
             class="mb-4 flex items-start justify-between gap-3 rounded-lg border px-4 py-3 text-sm {{ $map[$type] }}">
            <div>
                <p>{{ session($type) }}</p>
                @if (session('connection_code'))
                    <p class="mt-2 select-all rounded bg-white/60 px-2 py-1 font-mono text-base font-semibold tracking-wider">{{ session('connection_code') }}</p>
                @endif
            </div>
            <button @click="show = false" class="shrink-0 opacity-60 hover:opacity-100">&times;</button>
        </div>
    @endif
@endforeach
