@props(['roles' => []])

@php
    $labels = \App\Support\AccessHub::roles();
    $colors = [
        'requestor' => 'blue',
        'division_head' => 'purple',
        'vp' => 'amber',
        'user' => 'gray',
    ];
    // Show in config order for consistency.
    $ordered = array_values(array_intersect(array_keys($labels), (array) $roles));
@endphp

<span class="inline-flex flex-wrap gap-1">
    @forelse ($ordered as $role)
        <x-badge :color="$colors[$role] ?? 'gray'">{{ $labels[$role] ?? $role }}</x-badge>
    @empty
        <span class="text-xs text-gray-400">—</span>
    @endforelse
</span>
