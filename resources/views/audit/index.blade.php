<x-app-layout title="Audit log">
    <x-page-header title="Audit log" subtitle="Append only. Every grant, role change, scope change, enrollment and revoke." />

    <form method="GET" class="mb-4 flex flex-wrap items-end gap-3">
        <x-field label="Action" class="w-56">
            <x-select name="action">
                <option value="">All actions</option>
                @foreach ($actions as $action)
                    <option value="{{ $action }}" @selected(($filters['action'] ?? '') === $action)>{{ $action }}</option>
                @endforeach
            </x-select>
        </x-field>
        <x-button type="submit" variant="secondary">Filter</x-button>
        @if (array_filter($filters))
            <a href="{{ route('audit.index') }}" class="py-2 text-sm text-gray-500 hover:text-gray-800">Clear</a>
        @endif
    </form>

    @if ($entries->isEmpty())
        <x-empty title="No entries" message="Nothing matches this filter." />
    @else
        <x-table :head="['When', 'Actor', 'Action', 'Detail', 'IP']">
            @foreach ($entries as $entry)
                <tr>
                    <td class="whitespace-nowrap px-4 py-3 text-gray-500" title="{{ $entry->created_at }}">{{ $entry->created_at->diffForHumans() }}</td>
                    <td class="px-4 py-3 text-gray-700">{{ $entry->actor_label }}</td>
                    <td class="px-4 py-3"><code class="rounded bg-gray-100 px-1.5 py-0.5 text-xs">{{ $entry->action }}</code></td>
                    <td class="px-4 py-3 text-gray-900">{{ $entry->summary }}</td>
                    <td class="px-4 py-3 text-gray-400">{{ $entry->ip }}</td>
                </tr>
            @endforeach
        </x-table>

        <div class="mt-4">{{ $entries->links() }}</div>
    @endif
</x-app-layout>
