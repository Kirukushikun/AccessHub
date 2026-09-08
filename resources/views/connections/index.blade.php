<x-app-layout title="Connections">
    <x-page-header title="Connections" subtitle="One row per project per environment. Revoke staging without touching production." />

    @forelse ($grouped as $projectName => $connections)
        <div class="mb-6">
            <h2 class="mb-2 text-sm font-semibold text-gray-900">{{ $projectName }}</h2>
            <x-table :head="['Environment', 'Client ID', 'Last seen', 'Status', 'Actions']">
                @foreach ($connections as $conn)
                    <tr>
                        <td class="px-4 py-3 font-medium capitalize">{{ $conn->environment }}</td>
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
                        <td class="px-4 py-3">
                            @unless ($conn->revoked_at)
                                <div class="flex items-center gap-3">
                                    <form method="POST" action="{{ route('connections.regenerate', $conn->client_id) }}">
                                        @csrf
                                        <button class="text-sm text-gray-600 hover:text-gray-900 hover:underline">Regenerate</button>
                                    </form>
                                    <form method="POST" action="{{ route('connections.revoke', $conn->client_id) }}"
                                          onsubmit="return confirm('Revoke this connection? The project must re-enroll with a new code.')">
                                        @csrf
                                        <button class="text-sm text-red-600 hover:text-red-500 hover:underline">Revoke</button>
                                    </form>
                                </div>
                            @endunless
                        </td>
                    </tr>
                @endforeach
            </x-table>
        </div>
    @empty
        <x-empty title="No connections yet" message="Register a project and generate a connection code to enroll its first environment." />
    @endforelse
</x-app-layout>
