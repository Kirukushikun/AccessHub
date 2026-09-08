<x-app-layout title="Dashboard">
    <x-page-header title="Dashboard" subtitle="One list of people and their org role. Projects pull it on demand.">
        <x-slot:actions>
            <x-button variant="secondary" :href="route('people.create')">Add person</x-button>
            <x-button :href="route('projects.create')">Register project</x-button>
        </x-slot:actions>
    </x-page-header>

    <div class="grid grid-cols-2 gap-4 lg:grid-cols-4">
        <x-stat label="Active people" :value="$stats['people_active']" :hint="$stats['people_inactive'].' inactive'" />
        <x-stat label="Active projects" :value="$stats['projects_active']" />
        <x-stat label="Live connections" :value="$stats['connections_live']" />
        <x-stat label="Stale connections" :value="$stats['connections_stale']"
                :tone="$stats['connections_stale'] > 0 ? 'warn' : 'default'"
                hint="No sync in 14+ days" />
    </div>

    <div class="mt-6 grid gap-6 lg:grid-cols-5">
        <div class="lg:col-span-3">
            <x-card title="Connection health" :padded="false">
                <table class="min-w-full divide-y divide-gray-100 text-sm">
                    <tbody class="divide-y divide-gray-100">
                        @forelse ($connections as $c)
                            <tr>
                                <td class="px-5 py-3">
                                    <a href="{{ route('projects.show', $c->project) }}" class="font-medium text-gray-900 hover:underline">{{ $c->project->name }}</a>
                                    <x-badge class="ml-1">{{ $c->environment }}</x-badge>
                                </td>
                                <td class="px-5 py-3 text-right text-gray-500">
                                    <span class="{{ $c->isStale() ? 'text-amber-600' : '' }}">
                                        {{ $c->last_seen_at ? 'synced '.$c->last_seen_at->diffForHumans() : 'never synced' }}
                                    </span>
                                </td>
                            </tr>
                        @empty
                            <tr><td class="px-5 py-6 text-center text-sm text-gray-400">No live connections yet.</td></tr>
                        @endforelse
                    </tbody>
                </table>
                <div class="border-t border-gray-200 px-5 py-3 text-right">
                    <a href="{{ route('connections.index') }}" class="text-sm font-medium text-gray-700 hover:underline">All connections &rarr;</a>
                </div>
            </x-card>
        </div>

        <div class="lg:col-span-2">
            <x-card title="Recent activity" :padded="false">
                <ul class="divide-y divide-gray-100">
                    @forelse ($recentActivity as $entry)
                        <li class="px-5 py-3">
                            <p class="text-sm text-gray-900">{{ $entry->summary }}</p>
                            <p class="mt-0.5 text-xs text-gray-400">{{ $entry->action }} · {{ $entry->created_at->diffForHumans() }}</p>
                        </li>
                    @empty
                        <li class="px-5 py-6 text-center text-sm text-gray-400">Nothing logged yet.</li>
                    @endforelse
                </ul>
                <div class="border-t border-gray-200 px-5 py-3 text-right">
                    <a href="{{ route('audit.index') }}" class="text-sm font-medium text-gray-700 hover:underline">Audit log &rarr;</a>
                </div>
            </x-card>
        </div>
    </div>
</x-app-layout>
