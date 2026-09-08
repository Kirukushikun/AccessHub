@props(['name', 'title' => null])

{{--
    Trigger from anywhere with:
    <button @click="$dispatch('open-modal', 'the-name')">Open</button>
--}}
<div
    x-data="{ open: false }"
    x-on:open-modal.window="$event.detail === '{{ $name }}' && (open = true)"
    x-on:close-modal.window="$event.detail === '{{ $name }}' && (open = false)"
    x-on:keydown.escape.window="open = false"
    x-show="open"
    x-cloak
    class="fixed inset-0 z-50 flex items-center justify-center p-4"
>
    <div x-show="open" x-transition.opacity class="absolute inset-0 bg-gray-900/40" @click="open = false"></div>

    <div x-show="open" x-transition
         class="relative w-full max-w-md rounded-xl border border-gray-200 bg-white shadow-xl">
        @if ($title)
            <div class="flex items-center justify-between border-b border-gray-200 px-5 py-3">
                <h2 class="text-sm font-semibold text-gray-900">{{ $title }}</h2>
                <button @click="open = false" class="text-gray-400 hover:text-gray-700">&times;</button>
            </div>
        @endif
        <div class="p-5">
            {{ $slot }}
        </div>
    </div>
</div>
