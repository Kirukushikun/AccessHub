<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * Seeds the break-glass admin. Credentials come from BREAKGLASS_ADMIN_EMAIL /
 * BREAKGLASS_ADMIN_PASSWORD. If no password is set, a random one is generated
 * and printed once (non-production only) — document it offline.
 */
class AdminSeeder extends Seeder
{
    public function run(): void
    {
        $email = env('BREAKGLASS_ADMIN_EMAIL', 'admin@accesshub.test');
        $password = env('BREAKGLASS_ADMIN_PASSWORD') ?: null;

        if ($password === null) {
            if (app()->isProduction()) {
                $this->command->error('BREAKGLASS_ADMIN_PASSWORD is not set. Skipping admin seed.');

                return;
            }
            $password = Str::password(16);
            $this->command->warn("Break-glass admin password (shown once): {$password}");
        }

        User::updateOrCreate(
            ['email' => $email],
            [
                'name' => 'Break-glass Admin',
                'password' => Hash::make($password),
                'active' => true,
                'email_verified_at' => now(),
            ],
        );

        $this->command->info("Break-glass admin ready: {$email}");
    }
}
