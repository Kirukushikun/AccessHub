<x-app-layout title="Account">
    <x-page-header title="Account" :subtitle="auth()->user()->email" />

    <x-card class="max-w-md" title="Change password">
        <form method="POST" action="{{ route('account.password') }}" class="space-y-4">
            @csrf
            @method('PUT')

            <x-field label="Current password" :error="$errors->first('current_password')">
                <x-input type="password" name="current_password" required autocomplete="current-password" />
            </x-field>

            <x-field label="New password" :error="$errors->first('password')" hint="At least 8 characters.">
                <x-input type="password" name="password" required autocomplete="new-password" />
            </x-field>

            <x-field label="Confirm new password">
                <x-input type="password" name="password_confirmation" required autocomplete="new-password" />
            </x-field>

            <div class="flex justify-end">
                <x-button type="submit">Update password</x-button>
            </div>
        </form>
    </x-card>
</x-app-layout>
