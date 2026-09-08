<x-app-layout title="Add people">
    <x-page-header title="Add people" :back="route('people.index')"
                   subtitle="Tick everyone to bring in from the directory, then set the role and scope they share. Farm / department / position are filled in afterwards." />

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
              get allFilteredPicked() {
                  return this.filtered.length > 0 && this.filtered.every(p => this.picked.includes(p.id));
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
                            Set <code>USER_API_ENDPOINT</code> and <code>USER_API_KEY</code>, then check
                            <a href="{{ route('debug.user-api') }}" class="underline">/debug/user-api</a>.
                        </p>
                    </div>
                @else
                    <div class="flex items-center gap-3 border-b border-gray-200 p-3">
                        <x-input type="search" placeholder="Search name or email…" x-model="q" class="flex-1" />
                        <label class="flex shrink-0 items-center gap-1.5 text-xs text-gray-500">
                            <input type="checkbox" :checked="allFilteredPicked" @change="toggleAllFiltered" class="rounded border-gray-300">
                            Select all
                        </label>
                    </div>
                    <p class="px-4 pt-2 text-xs text-gray-400">
                        <span x-text="picked.length"></span> selected · {{ $directory->count() }} people not yet in the hub
                    </p>
                    <ul class="max-h-[26rem] divide-y divide-gray-100 overflow-y-auto">
                        <template x-for="p in filtered" :key="p.id">
                            <li>
                                <label class="flex cursor-pointer items-center gap-3 px-4 py-3 hover:bg-gray-50"
                                       :class="picked.includes(p.id) && 'bg-gray-50'">
                                    <input type="checkbox" name="user_ids[]" :value="p.id" x-model.number="picked" class="rounded border-gray-300">
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
            <x-card title="Roles">
                <x-field hint="Applied to everyone selected. Pick one or more.">
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
            </x-card>

            <x-card title="Scope" x-data="{ scope: '{{ old('scope', 'all') }}' }">
                <x-field>
                    <x-select name="scope" x-model="scope">
                        @foreach ($scopes as $value => $label)
                            <option value="{{ $value }}">{{ $label }}</option>
                        @endforeach
                    </x-select>
                </x-field>
                <div x-show="scope === 'selected'" x-cloak class="mt-3 space-y-2">
                    <p class="text-xs text-gray-400">Everyone selected applies only to the projects you tick.</p>
                    @foreach ($projects as $project)
                        <label class="flex items-center gap-2 text-sm">
                            <input type="checkbox" name="projects[]" value="{{ $project->key }}"
                                   @checked(in_array($project->key, old('projects', []), true)) class="rounded border-gray-300">
                            {{ $project->name }}
                        </label>
                    @endforeach
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
