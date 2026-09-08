<x-app-layout title="Admins">
    <x-page-header title="Admins" subtitle="Who can sign in to the hub. Deactivate rather than delete — it keeps the audit trail.">
        <x-slot:actions>
            <x-button @click="$dispatch('open-modal', 'add-admin')">Add admin</x-button>
        </x-slot:actions>
    </x-page-header>

    @error('active')
        <p class="mb-4 rounded-lg border border-red-200 bg-red-50 px-4 py-2 text-sm text-red-800">{{ $message }}</p>
    @enderror

    <x-table :head="['Name', 'Email', 'Status', '']">
        @foreach ($admins as $admin)
            <tr>
                <td class="px-4 py-3">
                    <span class="font-medium text-gray-900">{{ $admin->name }}</span>
                    @if ($admin->is(auth()->user()))
                        <x-badge class="ml-1">you</x-badge>
                    @endif
                </td>
                <td class="px-4 py-3 text-gray-600">{{ $admin->email }}</td>
                <td class="px-4 py-3">
                    <x-badge :color="$admin->active ? 'green' : 'gray'">{{ $admin->active ? 'Active' : 'Inactive' }}</x-badge>
                </td>
                <td class="px-4 py-3 text-right">
                    @unless ($admin->is(auth()->user()))
                        <form method="POST" action="{{ route('admins.update', $admin) }}"
                              @if ($admin->active) onsubmit="return confirm('Deactivate {{ $admin->name }}? They will be signed out and cannot log back in.')" @endif>
                            @csrf
                            @method('PUT')
                            <input type="hidden" name="name" value="{{ $admin->name }}">
                            <input type="hidden" name="active" value="{{ $admin->active ? '0' : '1' }}">
                            <button class="text-sm {{ $admin->active ? 'text-red-600 hover:text-red-500' : 'text-gray-600 hover:text-gray-900' }} hover:underline">
                                {{ $admin->active ? 'Deactivate' : 'Reactivate' }}
                            </button>
                        </form>
                    @endunless
                </td>
            </tr>
        @endforeach
    </x-table>

    <x-modal name="add-admin" title="Add admin">
        <form method="POST" action="{{ route('admins.store') }}" class="space-y-4">
            @csrf
            <x-field label="Name" :error="$errors->first('name')">
                <x-input name="name" required value="{{ old('name') }}" />
            </x-field>
            <x-field label="Email" :error="$errors->first('email')"
                     hint="A one-time password is generated and shown once after saving.">
                <x-input name="email" type="email" required value="{{ old('email') }}" />
            </x-field>
            <div class="flex justify-end gap-2">
                <x-button type="button" variant="secondary" @click="$dispatch('close-modal', 'add-admin')">Cancel</x-button>
                <x-button type="submit">Add admin</x-button>
            </div>
        </form>
    </x-modal>
</x-app-layout>
