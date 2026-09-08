<x-app-layout title="People">
    <x-page-header title="People" subtitle="Each person is stated once, with one org role.">
        <x-slot:actions>
            <x-button :href="route('people.create')">Add person</x-button>
        </x-slot:actions>
    </x-page-header>

    {{-- Filters --}}
    <form method="GET" class="mb-4 flex flex-wrap items-end gap-3">
        <x-field label="Farm" class="w-40">
            <x-select name="farm">
                <option value="">All farms</option>
                @foreach ($farms as $farm)
                    <option value="{{ $farm }}" @selected(($filters['farm'] ?? '') === $farm)>{{ $farm }}</option>
                @endforeach
            </x-select>
        </x-field>
        <x-field label="Department" class="w-44">
            <x-select name="department">
                <option value="">All departments</option>
                @foreach ($departments as $dept)
                    <option value="{{ $dept }}" @selected(($filters['department'] ?? '') === $dept)>{{ $dept }}</option>
                @endforeach
            </x-select>
        </x-field>
        <x-field label="Has role" class="w-44">
            <x-select name="role">
                <option value="">Any role</option>
                @foreach ($roles as $value => $label)
                    <option value="{{ $value }}" @selected(($filters['role'] ?? '') === $value)>{{ $label }}</option>
                @endforeach
            </x-select>
        </x-field>
        <x-button type="submit" variant="secondary">Filter</x-button>
        @if (array_filter($filters))
            <a href="{{ route('people.index') }}" class="py-2 text-sm text-gray-500 hover:text-gray-800">Clear</a>
        @endif
    </form>

    <div x-data="{
        selected: [],
        get all() { return {{ $people->pluck('user_id')->toJson() }}; },
        toggleAll(e) { this.selected = e.target.checked ? [...this.all] : []; },
    }">
        {{-- Bulk action bar --}}
        <div x-show="selected.length" x-cloak
             class="mb-3 flex flex-wrap items-center gap-3 rounded-lg border border-gray-200 bg-white px-4 py-2 text-sm shadow-sm">
            <span class="font-medium" x-text="selected.length + ' selected'"></span>
            <span class="text-gray-400">·</span>
            <button type="button" @click="$dispatch('open-modal', 'bulk-scope')"
                    class="font-medium text-gray-700 hover:underline">Point at project…</button>
            <button type="button" @click="selected = []" class="text-gray-500 hover:text-gray-800">Clear</button>
        </div>

        @if ($people->isEmpty())
            <x-empty title="No people match" message="Adjust the filters, or add someone from the directory.">
                <x-slot:action>
                    <x-button :href="route('people.create')">Add person</x-button>
                </x-slot:action>
            </x-empty>
        @else
            <div class="overflow-x-auto rounded-xl border border-gray-200 bg-white shadow-sm">
                <table class="min-w-full divide-y divide-gray-200 text-sm">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="w-10 px-4 py-3"><input type="checkbox" @change="toggleAll" class="rounded border-gray-300"></th>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">Name</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">Identity</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">Role</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">Scope</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">Status</th>
                            <th class="px-4 py-3"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @foreach ($people as $person)
                            <tr class="hover:bg-gray-50" :class="selected.includes({{ $person->user_id }}) && 'bg-gray-50'">
                                <td class="px-4 py-3">
                                    <input type="checkbox" value="{{ $person->user_id }}" x-model.number="selected" class="rounded border-gray-300">
                                </td>
                                <td class="px-4 py-3">
                                    <a href="{{ route('people.edit', $person->user_id) }}" class="font-medium text-gray-900 hover:underline">{{ $person->name }}</a>
                                    <p class="text-xs text-gray-400">#{{ $person->user_id }} · {{ $person->email }}</p>
                                </td>
                                <td class="px-4 py-3 text-gray-600">
                                    {{ $person->farm ?? '—' }}<span class="text-gray-300"> / </span>{{ $person->department ?? '—' }}
                                    @if ($person->position)
                                        <p class="text-xs text-gray-400">{{ $person->position }}</p>
                                    @endif
                                </td>
                                <td class="px-4 py-3"><x-roles :roles="$person->roles" /></td>
                                <td class="px-4 py-3 text-gray-600">
                                    @if ($person->scope === 'all')
                                        <span>All projects</span>
                                    @else
                                        <span>{{ count($person->projects) }} project{{ count($person->projects) === 1 ? '' : 's' }}</span>
                                    @endif
                                </td>
                                <td class="px-4 py-3">
                                    @if ($person->active)
                                        <x-badge color="green">Active</x-badge>
                                    @else
                                        <x-badge color="gray">Inactive</x-badge>
                                    @endif
                                </td>
                                <td class="px-4 py-3 text-right">
                                    <a href="{{ route('people.edit', $person->user_id) }}" class="text-sm text-gray-500 hover:text-gray-800">Edit</a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="mt-4">{{ $people->links() }}</div>
        @endif

        {{-- Bulk scope modal --}}
        <x-modal name="bulk-scope" title="Point selected people at a project">
            <form method="POST" action="{{ route('people.bulk-scope') }}" class="space-y-4">
                @csrf
                <template x-for="id in selected" :key="id">
                    <input type="hidden" name="user_ids[]" :value="id">
                </template>
                <p class="text-sm text-gray-500"><span x-text="selected.length"></span> people will have their scope set to <strong>selected</strong> and be added to:</p>
                <x-field label="Project">
                    <x-select name="project_key">
                        @foreach ($projects as $project)
                            <option value="{{ $project->key }}">{{ $project->name }}</option>
                        @endforeach
                    </x-select>
                </x-field>
                <div class="flex justify-end gap-2">
                    <x-button type="button" variant="secondary" @click="$dispatch('close-modal', 'bulk-scope')">Cancel</x-button>
                    <x-button type="submit">Apply</x-button>
                </div>
            </form>
        </x-modal>
    </div>
</x-app-layout>
