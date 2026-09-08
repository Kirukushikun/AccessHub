<?php

namespace Tests\Feature;

use App\Models\Connection;
use App\Models\Project;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class EnrollEndpointTest extends TestCase
{
    use RefreshDatabase;

    private function codeFor(Project $project, string $code = 'HUB-TEST-CODE', ?\DateTimeInterface $expires = null): void
    {
        $project->codes()->create([
            'code_hash' => Hash::make($code),
            'expires_at' => $expires ?? now()->addMinutes(15),
        ]);
    }

    public function test_valid_code_returns_credentials_once_and_creates_a_connection(): void
    {
        $project = Project::factory()->create(['key' => 'hrms']);
        $this->codeFor($project);

        $response = $this->postJson('/api/v1/enroll', [
            'code' => 'HUB-TEST-CODE',
            'project_key' => 'hrms',
            'environment' => 'local',
        ]);

        $response->assertOk()->assertJsonStructure(['client_id', 'client_secret']);

        $connection = Connection::where('client_id', $response->json('client_id'))->first();
        $this->assertNotNull($connection);
        $this->assertSame('local', $connection->environment);
        $this->assertTrue(Hash::check($response->json('client_secret'), $connection->client_secret_hash));
    }

    public function test_a_code_burns_after_first_use(): void
    {
        $project = Project::factory()->create(['key' => 'hrms']);
        $this->codeFor($project);

        $payload = ['code' => 'HUB-TEST-CODE', 'project_key' => 'hrms', 'environment' => 'local'];

        $this->postJson('/api/v1/enroll', $payload)->assertOk();
        $this->postJson('/api/v1/enroll', $payload)->assertStatus(422);
    }

    public function test_expired_code_is_rejected(): void
    {
        $project = Project::factory()->create(['key' => 'hrms']);
        $this->codeFor($project, expires: now()->subMinute());

        $this->postJson('/api/v1/enroll', [
            'code' => 'HUB-TEST-CODE', 'project_key' => 'hrms', 'environment' => 'local',
        ])->assertStatus(422);
    }

    public function test_code_is_scoped_to_its_project(): void
    {
        $a = Project::factory()->create(['key' => 'alpha']);
        Project::factory()->create(['key' => 'beta']);
        $this->codeFor($a);

        $this->postJson('/api/v1/enroll', [
            'code' => 'HUB-TEST-CODE', 'project_key' => 'beta', 'environment' => 'local',
        ])->assertStatus(422);
    }

    public function test_unknown_project_is_404(): void
    {
        $this->postJson('/api/v1/enroll', [
            'code' => 'x', 'project_key' => 'nope', 'environment' => 'local',
        ])->assertStatus(404);
    }

    public function test_re_enrolling_replaces_the_secret_and_clears_revocation(): void
    {
        $project = Project::factory()->create(['key' => 'hrms']);
        $old = Connection::factory()->for($project)->revoked()->create(['environment' => 'production']);
        $this->codeFor($project);

        $response = $this->postJson('/api/v1/enroll', [
            'code' => 'HUB-TEST-CODE', 'project_key' => 'hrms', 'environment' => 'production',
        ])->assertOk();

        $this->assertSame(1, $project->connections()->count());
        $fresh = $old->fresh();
        $this->assertNull($fresh->revoked_at);
        $this->assertTrue(Hash::check($response->json('client_secret'), $fresh->client_secret_hash));
    }

    public function test_enroll_is_rate_limited(): void
    {
        Project::factory()->create(['key' => 'hrms']);

        for ($i = 0; $i < 10; $i++) {
            $this->postJson('/api/v1/enroll', ['code' => 'x', 'project_key' => 'hrms', 'environment' => 'local']);
        }

        $this->postJson('/api/v1/enroll', ['code' => 'x', 'project_key' => 'hrms', 'environment' => 'local'])
            ->assertStatus(429);
    }
}
