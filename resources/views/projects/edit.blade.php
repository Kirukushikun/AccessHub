<x-app-layout :title="'Edit '.$project->name">
    <x-page-header :title="'Edit '.$project->name" :back="route('projects.show', $project->key)" />

    <x-card class="max-w-xl">
        <form method="POST" action="{{ route('projects.update', $project->key) }}" class="space-y-4">
            @csrf
            @method('PUT')

            <x-field label="Name" for="name">
                <x-input id="name" name="name" required value="{{ $project->name }}" />
            </x-field>

            <x-field label="Key" hint="Changing the key breaks existing connections. Usually leave alone.">
                <x-input name="key" value="{{ $project->key }}" />
            </x-field>

            <x-field label="Acceptance" for="acceptance">
                <x-select id="acceptance" name="acceptance">
                    @foreach ($acceptance as $value => $label)
                        <option value="{{ $value }}" @selected($project->acceptance === $value)>{{ $label }}</option>
                    @endforeach
                </x-select>
            </x-field>

            <label class="flex items-center gap-2 text-sm text-gray-700">
                <input type="checkbox" name="active" value="1" @checked($project->active) class="rounded border-gray-300">
                Active
            </label>

            <div class="flex justify-end gap-2 pt-2">
                <x-button variant="secondary" :href="route('projects.show', $project->key)">Cancel</x-button>
                <x-button type="submit">Save changes</x-button>
            </div>
        </form>
    </x-card>
</x-app-layout>
