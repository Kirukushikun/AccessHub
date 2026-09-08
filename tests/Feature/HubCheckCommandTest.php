<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class HubCheckCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_passes_when_everything_is_configured(): void
    {
        User::factory()->create(['active' => true]);
        Config::set('services.user_api.endpoint', 'https://directory.test/api/v1/users');
        Config::set('services.user_api.key', 'k');
        Http::fake(['directory.test/*' => Http::response([
            ['id' => Crypt::encryptString('7'), 'first_name' => 'Sam', 'last_name' => 'Ok', 'email' => 's@x.org'],
        ])]);

        $this->artisan('hub:check')
            ->expectsOutputToContain('Sam Ok')
            ->assertExitCode(0);
    }

    public function test_it_fails_when_the_directory_is_unreachable(): void
    {
        User::factory()->create(['active' => true]);
        Config::set('services.user_api.endpoint', '');

        $this->artisan('hub:check')->assertExitCode(1);
    }
}
