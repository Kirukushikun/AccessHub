@props(['active' => false])

<a {{ $attributes->merge(['class' => 'rounded-md px-3 py-2 text-sm font-medium '.($active
        ? 'bg-gray-900 text-white'
        : 'text-gray-600 hover:bg-gray-100 hover:text-gray-900')]) }}>
    {{ $slot }}
</a>
