@props(['title', 'subtitle' => null, 'back' => null])

<div class="mb-6 flex flex-wrap items-end justify-between gap-3">
    <div>
        @if ($back)
            <a href="{{ $back }}" class="mb-1 inline-block text-sm text-gray-500 hover:text-gray-800">&larr; Back</a>
        @endif
        <h1 class="text-xl font-semibold text-gray-900">{{ $title }}</h1>
        @if ($subtitle)
            <p class="mt-0.5 text-sm text-gray-500">{{ $subtitle }}</p>
        @endif
    </div>
    @isset($actions)
        <div class="flex items-center gap-2">{{ $actions }}</div>
    @endisset
</div>
