<x-app-layout title="Connecting a project">
    <x-page-header title="Connecting a project" subtitle="For hub admins — what to click, what to hand over, and why there are two identifiers instead of one.">
        <x-slot:actions>
            <x-button variant="secondary" :href="route('projects.index')">Go to Projects</x-button>
        </x-slot:actions>
    </x-page-header>

    @if (! $hasProjects)
        <div class="mb-6 rounded-lg border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800">
            No projects registered yet — <a href="{{ route('projects.create') }}" class="font-medium underline">register the first one</a> to get started.
        </div>
    @endif

    {{-- The short version --}}
    <x-card title="The short version" class="mb-6">
        <ol class="space-y-3 text-sm text-gray-700">
            <li class="flex gap-3">
                <span class="grid h-6 w-6 shrink-0 place-items-center rounded-full bg-gray-900 text-xs font-semibold text-white">1</span>
                <span><a href="{{ route('projects.create') }}" class="font-medium underline">Register the project</a> — you pick its <code class="rounded bg-gray-100 px-1 py-0.5 text-xs">project_key</code> right there (e.g. <code class="rounded bg-gray-100 px-1 py-0.5 text-xs">engistart</code>). Fixed from then on.</span>
            </li>
            <li class="flex gap-3">
                <span class="grid h-6 w-6 shrink-0 place-items-center rounded-full bg-gray-900 text-xs font-semibold text-white">2</span>
                <span>Open that project's page → <strong>Generate connection code</strong>. One-time, expires in 15 minutes.</span>
            </li>
            <li class="flex gap-3">
                <span class="grid h-6 w-6 shrink-0 place-items-center rounded-full bg-gray-900 text-xs font-semibold text-white">3</span>
                <span>Hand the target team all three: the hub URL, the <code class="rounded bg-gray-100 px-1 py-0.5 text-xs">project_key</code>, and the code — over a channel that isn't shared docs or git (chat DM, a password manager note).</span>
            </li>
            <li class="flex gap-3">
                <span class="grid h-6 w-6 shrink-0 place-items-center rounded-full bg-gray-900 text-xs font-semibold text-white">4</span>
                <span>Their app exchanges the code once for permanent credentials and stores those itself. The code is now dead — you never generate it again unless they lose the credentials.</span>
            </li>
        </ol>
    </x-card>

    {{-- What each piece is --}}
    <div class="mb-6">
        <h2 class="mb-2 text-sm font-semibold text-gray-900">What each piece is</h2>
        <x-table :head="['Piece', 'Secret?', 'Where it lives', 'Changes?']">
            <tr>
                <td class="px-4 py-3">
                    <span class="font-medium text-gray-900">Hub URL</span>
                    <p class="text-xs text-gray-400">{{ $hubUrl }}</p>
                </td>
                <td class="px-4 py-3"><x-badge color="gray">No</x-badge></td>
                <td class="px-4 py-3 text-gray-600">Their committed config</td>
                <td class="px-4 py-3 text-gray-600">Same for every project, forever</td>
            </tr>
            <tr>
                <td class="px-4 py-3">
                    <span class="font-medium text-gray-900">project_key</span>
                    <p class="text-xs text-gray-400">e.g. "engistart" — you choose it, at registration</p>
                </td>
                <td class="px-4 py-3"><x-badge color="gray">No — a public slug</x-badge></td>
                <td class="px-4 py-3 text-gray-600">Their committed config</td>
                <td class="px-4 py-3 text-gray-600">Set once, never again</td>
            </tr>
            <tr>
                <td class="px-4 py-3">
                    <span class="font-medium text-gray-900">Connection code</span>
                    <p class="text-xs text-gray-400">HUB-XXXX-XXXX</p>
                </td>
                <td class="px-4 py-3"><x-badge color="amber">Yes, briefly</x-badge></td>
                <td class="px-4 py-3 text-gray-600">Nowhere — used once, then dead</td>
                <td class="px-4 py-3 text-gray-600">Generate a new one any time</td>
            </tr>
            <tr>
                <td class="px-4 py-3">
                    <span class="font-medium text-gray-900">client_id / client_secret</span>
                    <p class="text-xs text-gray-400">returned once by enrollment</p>
                </td>
                <td class="px-4 py-3"><x-badge color="red">Yes</x-badge></td>
                <td class="px-4 py-3 text-gray-600"><strong>Their own database</strong> — never <code class="rounded bg-gray-100 px-1 py-0.5 text-xs">.env</code></td>
                <td class="px-4 py-3 text-gray-600">Survives redeploys; rotate via <a href="{{ route('connections.index') }}" class="underline">Connections</a></td>
            </tr>
        </x-table>
    </div>

    <div class="grid gap-6 lg:grid-cols-2">
        {{-- Why two identifiers --}}
        <x-card title="Why a key AND a code?">
            <div class="space-y-4 text-sm text-gray-700">
                <div>
                    <p class="font-medium text-gray-900">Why not just the URL?</p>
                    <p class="text-gray-600">One hub, many projects — every project hits the exact same URL. The URL says which server; <code class="rounded bg-gray-100 px-1 py-0.5 text-xs">project_key</code> says which of the many registered tenants is asking.</p>
                </div>
                <div>
                    <p class="font-medium text-gray-900">Why isn't the key enough by itself?</p>
                    <p class="text-gray-600"><code class="rounded bg-gray-100 px-1 py-0.5 text-xs">project_key</code> is a public slug — it shows up in configs, docs, git history. If it alone were enough to enroll, anyone who's ever seen a project's name could self-enroll as that project and pull its whole grant list.</p>
                </div>
                <div>
                    <p class="font-medium text-gray-900">So what does the code actually prove?</p>
                    <p class="text-gray-600">That an admin, on this hub, right now, authorized this specific enrollment. It's single-use and 15 minutes — nothing about it is guessable or reusable, unlike the key.</p>
                </div>
                <p class="text-xs text-gray-400">Key = a username. Code = a one-time invite that proves the username is legitimate.</p>
            </div>
        </x-card>

        {{-- What the target project needs to build --}}
        <x-card title="What the target project builds">
            <p class="mb-3 text-sm text-gray-500">
                Send whoever's building the integration the hub's integration guide
                (<code class="rounded bg-gray-100 px-1 py-0.5 text-xs">docs/hub-integration-guide.md</code> in the hub repo) — this is the two-call summary.
            </p>
            <div class="space-y-3 font-mono text-xs">
                <div class="rounded-lg bg-gray-900 p-3 text-gray-100">
                    <p class="text-gray-400"># once, to enroll</p>
                    <p>POST {{ $hubUrl }}/api/v1/enroll</p>
                    <p>code, project_key, environment</p>
                    <p class="text-gray-400">→ {"{"} client_id, client_secret {"}"}  (shown once)</p>
                </div>
                <div class="rounded-lg bg-gray-900 p-3 text-gray-100">
                    <p class="text-gray-400"># every sync after that</p>
                    <p>GET {{ $hubUrl }}/api/v1/grants</p>
                    <p>X-Client-Id, X-Client-Secret</p>
                    <p class="text-gray-400">→ {"{"} people: [{"{"} user_id, roles: [...], active {"}"}] {"}"}</p>
                </div>
            </div>
            <p class="mt-3 text-xs text-gray-400">
                <code class="rounded bg-gray-100 px-1 py-0.5">roles</code> is always an array — a person can hold more than one.
                <code class="rounded bg-gray-100 px-1 py-0.5">active: false</code> means removed at the hub; that's the revoke signal, not something to filter out.
            </p>
        </x-card>
    </div>
</x-app-layout>
