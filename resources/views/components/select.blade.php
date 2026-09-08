<select
    {{ $attributes->merge(['class' => 'block w-full rounded-md border border-gray-300 bg-white px-3 py-2 text-sm shadow-sm focus:border-gray-900 focus:outline-none focus:ring-1 focus:ring-gray-900']) }}>
    {{ $slot }}
</select>
