<x-app-layout :title="$project->name">
    <x-page-header :title="$project->name" :back="route('projects.index')">
        <x-slot:subtitle>
            <code class="rounded bg-gray-100 px-1.5 py-0.5 text-xs">{{ $project->key }}</code>
        </x-slot:subtitle>
        <x-slot:actions>
            <x-button variant="secondary" :href="route('projects.edit', $project->key)">Edit</x-button>
            <form method="POST" action="{{ route('projects.connection-code', $project->key) }}">
                @csrf
                <x-button type="submit">Generate connection code</x-button>
            </form>
        </x-slot:actions>
    </x-page-header>

    <div class="grid gap-3 sm:grid-cols-3">
        <x-stat label="Acceptance" :value="$acceptance[$project->acceptance]" />
        <x-stat label="Status" :value="$project->active ? 'Active' : 'Inactive'" />
        <x-stat label="People in scope" :value="$people->count()" hint="What a sync would return now" />
    </div>

    @if ($usableCodes > 0)
        <p class="mt-4 rounded-lg border border-amber-200 bg-amber-50 px-4 py-2 text-sm text-amber-800">
            {{ $usableCodes }} unused connection code{{ $usableCodes === 1 ? '' : 's' }} outstanding (expire within {{ \App\Support\AccessHub::codeTtlMinutes() }} min of issue).
        </p>
    @endif

    {{-- Connections per environment --}}
    <div class="mt-6">
        <h2 class="mb-2 text-sm font-semibold text-gray-900">Connections</h2>
        <x-table :head="['Environment', 'Client ID', 'Last seen', 'Status', '']">
            @foreach ($environments as $env)
                @php $conn = $connections->firstWhere('environment', $env); @endphp
                <tr>
                    <td class="px-4 py-3 font-medium capitalize">{{ $env }}</td>
                    @if ($conn)
                        <td class="px-4 py-3"><code class="text-xs text-gray-600">{{ $conn->client_id }}</code></td>
                        <td class="px-4 py-3 text-gray-500">
                            @if ($conn->revoked_at)
                                revoked {{ $conn->revoked_at->diffForHumans() }}
                            @elseif ($conn->last_seen_at)
                                {{ $conn->last_seen_at->diffForHumans() }}
                            @else
                                never synced
                            @endif
                        </td>
                        <td class="px-4 py-3">
                            @php $status = $conn->status(); @endphp
                            <x-badge :color="['revoked' => 'red', 'stale' => 'amber', 'live' => 'green'][$status]">{{ ucfirst($status) }}</x-badge>
                        </td>
                        <td class="px-4 py-3 text-right">
                            @unless ($conn->revoked_at)
                                <a href="{{ route('connections.index') }}" class="text-sm text-gray-500 hover:text-gray-800">Manage</a>
                            @endunless
                        </td>
                    @else
                        <td class="px-4 py-3 text-gray-400" colspan="3">Not enrolled</td>
                        <td class="px-4 py-3 text-right">
                            <form method="POST" action="{{ route('projects.connection-code', $project->key) }}">
                                @csrf
                                <button class="text-sm font-medium text-gray-700 hover:underline">Generate code</button>
                            </form>
                        </td>
                    @endif
                </tr>
            @endforeach
        </x-table>
    </div>

    {{-- Preview of grant list --}}
    <div class="mt-6">
        <h2 class="mb-2 text-sm font-semibold text-gray-900">Grant list preview</h2>
        <p class="mb-2 text-xs text-gray-400">What <code>GET /api/v1/grants</code> would return for this project right now.</p>
        <x-table :head="['User', 'Role', 'Active']">
            @foreach ($people as $person)
                <tr>
                    <td class="px-4 py-3">
                        <span class="font-medium text-gray-900">{{ $person->name }}</span>
                        <span class="text-xs text-gray-400"> · #{{ $person->user_id }}</span>
                    </td>
                    <td class="px-4 py-3"><x-roles :roles="$person->roles" /></td>
                    <td class="px-4 py-3">
                        <x-badge :color="$person->active ? 'green' : 'gray'">{{ $person->active ? 'true' : 'false' }}</x-badge>
                    </td>
                </tr>
            @endforeach
        </x-table>
    </div>
</x-app-layout>
