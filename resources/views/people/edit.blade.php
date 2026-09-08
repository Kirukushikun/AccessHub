<x-app-layout :title="$person->name">
    <x-page-header :title="$person->name" :back="route('people.index')"
                   :subtitle="'#'.$person->user_id.' · '.$person->email" />

    <form method="POST" action="{{ route('people.update', $person->user_id) }}" class="grid gap-6 lg:grid-cols-2">
        @csrf
        @method('PUT')

        <x-card title="Roles">
            <x-field hint="One or more.">
                <div class="space-y-2">
                    @foreach ($roles as $value => $label)
                        <label class="flex items-center gap-2 text-sm">
                            <input type="checkbox" name="roles[]" value="{{ $value }}"
                                   @checked(in_array($value, old('roles', $person->roles ?? []), true)) class="rounded border-gray-300">
                            {{ $label }}
                        </label>
                    @endforeach
                </div>
            </x-field>
            @error('roles') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
        </x-card>

        <x-card title="Scope" x-data="{ scope: '{{ $person->scope }}' }">
            <x-field>
                <x-select name="scope" x-model="scope">
                    @foreach ($scopes as $value => $label)
                        <option value="{{ $value }}" @selected($person->scope === $value)>{{ $label }}</option>
                    @endforeach
                </x-select>
            </x-field>
            <div x-show="scope === 'selected'" x-cloak class="mt-3 space-y-2">
                @foreach ($projects as $project)
                    <label class="flex items-center gap-2 text-sm">
                        <input type="checkbox" name="projects[]" value="{{ $project->key }}"
                               @checked(in_array($project->key, $selectedProjectKeys, true)) class="rounded border-gray-300">
                        {{ $project->name }}
                    </label>
                @endforeach
            </div>
        </x-card>

        <x-card title="Identity (display only)" class="lg:col-span-2">
            <div class="grid gap-3 sm:grid-cols-3">
                <x-field label="Farm">
                    <x-select name="farm">
                        <option value="">—</option>
                        @foreach ($farms as $farm)
                            <option value="{{ $farm }}" @selected($person->farm === $farm)>{{ $farm }}</option>
                        @endforeach
                    </x-select>
                </x-field>
                <x-field label="Department">
                    <x-select name="department">
                        <option value="">—</option>
                        @foreach ($departments as $dept)
                            <option value="{{ $dept }}" @selected($person->department === $dept)>{{ $dept }}</option>
                        @endforeach
                    </x-select>
                </x-field>
                <x-field label="Position"><x-input name="position" value="{{ $person->position }}" /></x-field>
            </div>
        </x-card>

        <div class="flex items-center justify-between lg:col-span-2">
            <label class="flex items-center gap-2 text-sm text-gray-700">
                <input type="checkbox" name="active" value="1" @checked($person->active) class="rounded border-gray-300">
                Active — uncheck to mark as departed (keeps history)
            </label>
            <div class="flex gap-2">
                <x-button variant="secondary" :href="route('people.index')">Cancel</x-button>
                <x-button type="submit">Save changes</x-button>
            </div>
        </div>
    </form>
</x-app-layout>
