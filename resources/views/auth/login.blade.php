<x-guest-layout title="Sign in">
    <x-card>
        <form method="POST" action="{{ route('login.store') }}" class="space-y-4">
            @csrf

            <x-field label="Email" for="email" :error="$errors->first('email')">
                <x-input id="email" name="email" type="email" required autofocus autocomplete="username"
                         value="{{ old('email') }}" placeholder="you@bfcgroup.org" />
            </x-field>

            <x-field label="Password" for="password" :error="$errors->first('password')">
                <div x-data="{ show: false }" class="relative">
                    <x-input id="password" name="password" ::type="show ? 'text' : 'password'" type="password"
                             required autocomplete="current-password" class="pr-16" />
                    <button type="button" @click="show = !show"
                            class="absolute inset-y-0 right-0 px-3 text-xs font-medium text-gray-500 hover:text-gray-800"
                            x-text="show ? 'Hide' : 'Show'">Show</button>
                </div>
            </x-field>

            @if ($turnstileSiteKey)
                <div>
                    <div class="cf-turnstile" data-sitekey="{{ $turnstileSiteKey }}" data-callback="onTurnstile"></div>
                    <input type="hidden" name="turnstile_token" id="turnstile_token">
                    @error('turnstile_token') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>
                <script src="https://challenges.cloudflare.com/turnstile/v0/api.js" async defer></script>
                <script>function onTurnstile(t){document.getElementById('turnstile_token').value=t;}</script>
            @endif

            <x-button type="submit" class="w-full">Sign in</x-button>
        </form>
    </x-card>

    <p class="mt-4 text-center text-xs text-gray-400">Admin access only. Attempts are logged.</p>
</x-guest-layout>
