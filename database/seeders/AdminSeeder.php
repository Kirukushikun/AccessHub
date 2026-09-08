<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * Seeds the single break-glass admin from BREAKGLASS_ADMIN_EMAIL /
 * BREAKGLASS_ADMIN_PASSWORD.
 *
 *  - Safe to re-run: it never overwrites the password of an admin that already
 *    exists (so a redeploy won't reset a password the admin has since changed).
 *  - Production: both env vars are required; the seeder aborts if either is missing.
 *  - Local: a random password is generated and printed once if none is set.
 */
class AdminSeeder extends Seeder
{
    public function run(): void
    {
        $email = trim((string) env('BREAKGLASS_ADMIN_EMAIL'));
        $password = (string) env('BREAKGLASS_ADMIN_PASSWORD');

        if ($email === '') {
            $message = 'BREAKGLASS_ADMIN_EMAIL is not set — cannot seed the admin.';
            if (app()->isProduction()) {
                throw new \RuntimeException($message);
            }
            $this->command->error($message);

            return;
        }

        if ($existing = User::where('email', $email)->first()) {
            if (! $existing->active) {
                $existing->update(['active' => true]);
                $this->command->warn("Reactivated existing admin: {$email}");
            } else {
                $this->command->info("Admin already exists, left untouched: {$email}");
            }

            return;
        }

        if ($password === '') {
            if (app()->isProduction()) {
                throw new \RuntimeException('BREAKGLASS_ADMIN_PASSWORD is not set — cannot seed the admin.');
            }
            $password = Str::password(16);
            $this->command->warn("Generated admin password (shown once): {$password}");
        }

        User::create([
            'name' => 'Administrator',
            'email' => $email,
            'password' => Hash::make($password),
            'active' => true,
            'email_verified_at' => now(),
        ]);

        $this->command->info("Break-glass admin created: {$email}");
    }
}
