@props(['title' => null, 'padded' => true])

<div {{ $attributes->merge(['class' => 'rounded-xl border border-gray-200 bg-white shadow-sm']) }}>
    @if ($title)
        <div class="border-b border-gray-200 px-5 py-3">
            <h2 class="text-sm font-semibold text-gray-900">{{ $title }}</h2>
        </div>
    @endif
    <div class="{{ $padded ? 'p-5' : '' }}">
        {{ $slot }}
    </div>
</div>
