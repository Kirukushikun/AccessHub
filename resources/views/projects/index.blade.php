<x-app-layout title="Projects">
    <x-page-header title="Projects" subtitle="Registered systems that can pull the grant list.">
        <x-slot:actions>
            <x-button :href="route('projects.create')">Register project</x-button>
        </x-slot:actions>
    </x-page-header>

    @if ($projects->isEmpty())
        <x-empty title="No projects registered" message="Register a system so it can enroll and sync.">
            <x-slot:action><x-button :href="route('projects.create')">Register project</x-button></x-slot:action>
        </x-empty>
    @else
        <x-table :head="['Project', 'Key', 'Acceptance', 'Connections', 'Status', '']">
            @foreach ($projects as $project)
                <tr class="hover:bg-gray-50">
                    <td class="px-4 py-3">
                        <a href="{{ route('projects.show', $project->key) }}" class="font-medium text-gray-900 hover:underline">{{ $project->name }}</a>
                    </td>
                    <td class="px-4 py-3"><code class="rounded bg-gray-100 px-1.5 py-0.5 text-xs">{{ $project->key }}</code></td>
                    <td class="px-4 py-3">
                        <x-badge :color="$project->acceptance === 'explicit' ? 'amber' : 'gray'">{{ $acceptance[$project->acceptance] }}</x-badge>
                    </td>
                    <td class="px-4 py-3 text-gray-600">{{ $project->live_connections_count }} live</td>
                    <td class="px-4 py-3">
                        <x-badge :color="$project->active ? 'green' : 'gray'">{{ $project->active ? 'Active' : 'Inactive' }}</x-badge>
                    </td>
                    <td class="px-4 py-3 text-right">
                        <a href="{{ route('projects.show', $project->key) }}" class="text-sm text-gray-500 hover:text-gray-800">Open</a>
                    </td>
                </tr>
            @endforeach
        </x-table>
    @endif
</x-app-layout>
