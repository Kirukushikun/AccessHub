<x-app-layout title="Register project">
    <x-page-header title="Register project" :back="route('projects.index')"
                   subtitle="Add a system so it can enroll and pull grants." />

    <x-card class="max-w-xl">
        <form method="POST" action="{{ route('projects.store') }}" class="space-y-4">
            @csrf

            <x-field label="Name" for="name" :error="$errors->first('name')">
                <x-input id="name" name="name" required placeholder="HR Management System" value="{{ old('name') }}" />
            </x-field>

            <x-field label="Key" for="key" hint="Short slug the project sends on enroll and sync. Lowercase, no spaces."
                     :error="$errors->first('key')">
                <x-input id="key" name="key" required placeholder="hrms" value="{{ old('key') }}" />
            </x-field>

            <x-field label="Acceptance" for="acceptance"
                     hint="Explicit ignores 'all'-scoped people — for sensitive systems where a new VP should not silently appear.">
                <x-select id="acceptance" name="acceptance">
                    @foreach ($acceptance as $value => $label)
                        <option value="{{ $value }}">{{ $label }}</option>
                    @endforeach
                </x-select>
            </x-field>

            <div class="flex justify-end gap-2 pt-2">
                <x-button variant="secondary" :href="route('projects.index')">Cancel</x-button>
                <x-button type="submit">Register</x-button>
            </div>
        </form>
    </x-card>
</x-app-layout>
