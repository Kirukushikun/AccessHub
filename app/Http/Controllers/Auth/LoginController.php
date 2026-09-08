<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\AccessLog;
use App\Models\User;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class LoginController extends Controller
{
    public function create(): View
    {
        return view('auth.login', [
            'turnstileSiteKey' => config('services.turnstile.verify')
                ? config('services.turnstile.site_key')
                : null,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        $email = $credentials['email'];

        $this->verifyTurnstile($request);

        if ($this->lockedOut($email)) {
            throw ValidationException::withMessages([
                'email' => 'Account temporarily locked. Try again in '
                    .config('access-hub.login_lockout_minutes').' minutes.',
            ]);
        }

        $user = User::where('email', $email)->first();

        if (! $user || ! $user->active || ! Hash::check($credentials['password'], $user->password)) {
            $this->recordFailure($email, $request);

            $remaining = config('access-hub.login_max_attempts') - $this->attempts($email);

            throw ValidationException::withMessages([
                'email' => $remaining > 0
                    ? "Incorrect email or password. {$remaining} attempt(s) remaining."
                    : 'Incorrect email or password.',
            ]);
        }

        $this->clearAttempts($email);
        AccessLog::create([
            'email' => $email,
            'successful' => true,
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        Auth::login($user, remember: false);
        $request->session()->regenerate();

        return redirect()->intended(route('dashboard'));
    }

    public function destroy(Request $request): RedirectResponse
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }

    private function verifyTurnstile(Request $request): void
    {
        if (! config('services.turnstile.verify')) {
            return;
        }

        try {
            $verify = Http::asForm()->timeout(5)->connectTimeout(3)
                ->post('https://challenges.cloudflare.com/turnstile/v0/siteverify', [
                    'secret' => config('services.turnstile.secret'),
                    'response' => $request->input('turnstile_token', ''),
                    'remoteip' => $request->ip(),
                ]);
        } catch (ConnectionException $e) {
            Log::warning('Turnstile unreachable', ['error' => $e->getMessage()]);
            throw ValidationException::withMessages([
                'turnstile_token' => 'Human verification service is unreachable. Please try again shortly.',
            ]);
        }

        if (! ($verify->json('success') ?? false)) {
            throw ValidationException::withMessages([
                'turnstile_token' => 'Human verification failed. Please complete the challenge and try again.',
            ]);
        }
    }

    private function lockKey(string $email): string
    {
        return 'login_lockout_'.sha1($email);
    }

    private function attemptKey(string $email): string
    {
        return 'login_attempts_'.sha1($email);
    }

    private function lockedOut(string $email): bool
    {
        return Cache::has($this->lockKey($email));
    }

    private function attempts(string $email): int
    {
        return (int) Cache::get($this->attemptKey($email), 0);
    }

    private function recordFailure(string $email, Request $request): void
    {
        AccessLog::create([
            'email' => $email,
            'successful' => false,
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        $attempts = $this->attempts($email) + 1;
        $window = now()->addMinutes(config('access-hub.login_lockout_minutes'));

        Cache::put($this->attemptKey($email), $attempts, $window);

        if ($attempts >= config('access-hub.login_max_attempts')) {
            Cache::put($this->lockKey($email), true, $window);
        }
    }

    private function clearAttempts(string $email): void
    {
        Cache::forget($this->attemptKey($email));
        Cache::forget($this->lockKey($email));
    }
}
