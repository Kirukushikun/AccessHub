<?php

namespace Database\Factories;

use App\Models\Connection;
use App\Models\Project;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends Factory<Connection>
 */
class ConnectionFactory extends Factory
{
    public function definition(): array
    {
        return [
            'project_id' => Project::factory(),
            'environment' => fake()->randomElement(config('access-hub.environments')),
            'client_id' => 'chub_'.Str::lower(Str::random(24)),
            'client_secret_hash' => Hash::make('secret-'.Str::random(8)),
            'last_seen_at' => now()->subHours(fake()->numberBetween(1, 72)),
            'revoked_at' => null,
        ];
    }

    /** Give the connection a known plaintext secret (for API tests). */
    public function withSecret(string $secret): static
    {
        return $this->state(['client_secret_hash' => Hash::make($secret)]);
    }

    public function stale(): static
    {
        return $this->state(['last_seen_at' => now()->subDays(30)]);
    }

    public function revoked(): static
    {
        return $this->state(['revoked_at' => now()->subDay()]);
    }
}
