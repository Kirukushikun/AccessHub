<x-app-layout title="Add people">
    <x-page-header title="Add people" :back="route('people.index')"
                   subtitle="Search and tick everyone who should get the same role and scope, then add them all in one go. Farm / department / position are filled in afterwards." />

    <form method="POST" action="{{ route('people.store') }}"
          x-data="{
              q: '',
              picked: [],
              people: {{ Illuminate\Support\Js::from($directory->map(fn ($p) => [
                  'id' => $p['user_id'],
                  'name' => $p['name'],
                  'email' => $p['email'],
                  'search' => strtolower($p['name'].' '.$p['email']),
              ])) }},
              get filtered() {
                  const q = this.q.trim().toLowerCase();
                  return q === '' ? this.people : this.people.filter(p => p.search.includes(q));
              },
              get selectedPeople() {
                  return this.picked
                      .map(id => this.people.find(p => p.id === id))
                      .filter(Boolean);
              },
              get allFilteredPicked() {
                  return this.filtered.length > 0 && this.filtered.every(p => this.picked.includes(p.id));
              },
              toggle(id) {
                  this.picked = this.picked.includes(id)
                      ? this.picked.filter(x => x !== id)
                      : [...this.picked, id];
              },
              toggleAllFiltered(e) {
                  const ids = this.filtered.map(p => p.id);
                  this.picked = e.target.checked
                      ? [...new Set([...this.picked, ...ids])]
                      : this.picked.filter(id => !ids.includes(id));
              },
          }"
          class="grid gap-6 lg:grid-cols-5">
        @csrf

        {{-- Directory picker --}}
        <div class="lg:col-span-3">
            <x-card title="Directory" :padded="false">
                @if ($directoryError)
                    <div class="p-4">
                        <x-empty title="Directory unavailable" :message="$directoryError" />
                        <p class="mt-3 text-center text-xs text-gray-400">
                            Check <code>USER_API_ENDPOINT</code> / <code>USER_API_KEY</code> and run
                            <code>php artisan hub:check</code> on the server.
                        </p>
                    </div>
                @else
                    {{-- hidden inputs are the source of truth for the POST --}}
                    <template x-for="id in picked" :key="id">
                        <input type="hidden" name="user_ids[]" :value="id">
                    </template>

                    <div class="flex items-center gap-3 border-b border-gray-200 p-3">
                        <x-input type="search" placeholder="Search name or email…" x-model="q" class="flex-1" />
                        <label class="flex shrink-0 items-center gap-1.5 text-xs text-gray-500">
                            <input type="checkbox" :checked="allFilteredPicked" @change="toggleAllFiltered" class="rounded border-gray-300">
                            Select all
                        </label>
                    </div>

                    {{-- Running selection — always visible, survives searching --}}
                    <div x-show="picked.length" x-cloak class="border-b border-gray-200 bg-gray-50 px-4 py-3">
                        <div class="mb-2 flex items-center justify-between">
                            <span class="text-xs font-medium text-gray-600">
                                Selected — <span x-text="picked.length"></span>
                            </span>
                            <button type="button" @click="picked = []" class="text-xs text-gray-500 hover:text-gray-800">Clear all</button>
                        </div>
                        <div class="flex flex-wrap gap-1.5">
                            <template x-for="p in selectedPeople" :key="p.id">
                                <span class="inline-flex items-center gap-1 rounded-full border border-gray-300 bg-white py-0.5 pl-2 pr-1 text-xs">
                                    <span x-text="p.name"></span>
                                    <button type="button" @click="toggle(p.id)" class="text-gray-400 hover:text-gray-700" aria-label="Remove">&times;</button>
                                </span>
                            </template>
                        </div>
                    </div>

                    <p class="px-4 pt-2 text-xs text-gray-400">{{ $directory->count() }} people not yet in the hub</p>
                    <ul class="max-h-[24rem] divide-y divide-gray-100 overflow-y-auto">
                        <template x-for="p in filtered" :key="p.id">
                            <li>
                                <label class="flex cursor-pointer items-center gap-3 px-4 py-3 hover:bg-gray-50"
                                       :class="picked.includes(p.id) && 'bg-gray-50'">
                                    <input type="checkbox" :checked="picked.includes(p.id)" @change="toggle(p.id)" class="rounded border-gray-300">
                                    <span class="flex-1">
                                        <span class="font-medium text-gray-900" x-text="p.name"></span>
                                        <span class="block text-xs text-gray-400">#<span x-text="p.id"></span> · <span x-text="p.email"></span></span>
                                    </span>
                                </label>
                            </li>
                        </template>
                        <li x-show="filtered.length === 0" class="px-4 py-6 text-center text-sm text-gray-400">No match.</li>
                    </ul>
                @endif
            </x-card>
            @error('user_ids') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
            @error('user_ids.*') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
        </div>

        {{-- Shared role + scope --}}
        <div class="space-y-6 lg:col-span-2">
            <x-card>
                <p class="mb-3 text-sm text-gray-500">
                    Applied to <strong><span x-text="picked.length || 'the'"></span></strong>
                    <span x-text="picked.length === 1 ? 'selected person' : 'selected people'">selected people</span>.
                </p>

                <x-field label="Roles" hint="Pick one or more.">
                    <div class="space-y-2">
                        @foreach ($roles as $value => $label)
                            <label class="flex items-center gap-2 text-sm">
                                <input type="checkbox" name="roles[]" value="{{ $value }}"
                                       @checked(in_array($value, old('roles', []), true)) class="rounded border-gray-300">
                                {{ $label }}
                            </label>
                        @endforeach
                    </div>
                </x-field>
                @error('roles') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror

                <div class="mt-4" x-data="{ scope: '{{ old('scope', 'all') }}' }">
                    <x-field label="Scope">
                        <x-select name="scope" x-model="scope">
                            @foreach ($scopes as $value => $label)
                                <option value="{{ $value }}">{{ $label }}</option>
                            @endforeach
                        </x-select>
                    </x-field>
                    <div x-show="scope === 'selected'" x-cloak class="mt-3 space-y-2">
                        <p class="text-xs text-gray-400">They apply only to the projects you tick.</p>
                        @foreach ($projects as $project)
                            <label class="flex items-center gap-2 text-sm">
                                <input type="checkbox" name="projects[]" value="{{ $project->key }}"
                                       @checked(in_array($project->key, old('projects', []), true)) class="rounded border-gray-300">
                                {{ $project->name }}
                            </label>
                        @endforeach
                    </div>
                </div>
            </x-card>

            <div class="flex justify-end gap-2">
                <x-button variant="secondary" :href="route('people.index')">Cancel</x-button>
                <x-button type="submit" ::disabled="picked.length === 0">
                    <span x-text="picked.length > 1 ? `Add ${picked.length} people` : 'Add person'">Add person</span>
                </x-button>
            </div>
        </div>
    </form>
</x-app-layout>
