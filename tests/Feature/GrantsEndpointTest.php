<?php

namespace Tests\Feature;

use App\Models\Connection;
use App\Models\Person;
use App\Models\Project;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GrantsEndpointTest extends TestCase
{
    use RefreshDatabase;

    private function connect(Project $project, string $secret = 'sync-secret'): array
    {
        $connection = Connection::factory()->for($project)->withSecret($secret)->create([
            'environment' => 'production',
            'revoked_at' => null,
        ]);

        return [
            'X-Client-Id' => $connection->client_id,
            'X-Client-Secret' => $secret,
        ];
    }

    public function test_open_project_returns_all_scoped_plus_pointed_people(): void
    {
        $project = Project::factory()->create(['acceptance' => 'open']);
        $all = Person::factory()->create(['scope' => 'all', 'name' => 'Ada All']);
        $pointed = Person::factory()->selectedScope()->create(['name' => 'Pat Pointed']);
        $pointed->projects()->attach($project);
        $other = Person::factory()->selectedScope()->create(['name' => 'Otto Other']);
        $other->projects()->attach(Project::factory()->create());

        $response = $this->getJson('/api/v1/grants', $this->connect($project));

        $response->assertOk();
        $names = collect($response->json('people'))->pluck('name');
        $this->assertEqualsCanonicalizing(['Ada All', 'Pat Pointed'], $names->all());
    }

    public function test_explicit_project_ignores_all_scoped_people(): void
    {
        $project = Project::factory()->explicit()->create();
        Person::factory()->create(['scope' => 'all']);
        $pointed = Person::factory()->selectedScope()->create(['name' => 'Only Me']);
        $pointed->projects()->attach($project);

        $response = $this->getJson('/api/v1/grants', $this->connect($project));

        $response->assertOk()->assertJsonCount(1, 'people');
        $this->assertSame('Only Me', $response->json('people.0.name'));
    }

    public function test_inactive_people_are_returned_with_active_false(): void
    {
        $project = Project::factory()->create(['acceptance' => 'open']);
        Person::factory()->inactive()->create(['scope' => 'all', 'name' => 'Gone Guy']);

        $response = $this->getJson('/api/v1/grants', $this->connect($project));

        $response->assertOk()->assertJsonPath('people.0.name', 'Gone Guy')
            ->assertJsonPath('people.0.active', false);
    }

    public function test_response_has_the_documented_shape(): void
    {
        $project = Project::factory()->create(['acceptance' => 'open']);
        Person::factory()->roles(['requestor', 'vp'])->create(['scope' => 'all']);

        $this->getJson('/api/v1/grants', $this->connect($project))
            ->assertOk()
            ->assertJsonStructure([
                'generated_at',
                'people' => [['user_id', 'name', 'email', 'farm', 'department', 'position', 'roles', 'active']],
            ])
            ->assertJsonPath('people.0.roles', ['requestor', 'vp']);
    }

    public function test_bad_credentials_are_rejected(): void
    {
        $project = Project::factory()->create();
        $headers = $this->connect($project);
        $headers['X-Client-Secret'] = 'wrong';

        $this->getJson('/api/v1/grants', $headers)->assertStatus(401);
        $this->getJson('/api/v1/grants')->assertStatus(401);
    }

    public function test_revoked_connection_is_forbidden(): void
    {
        $project = Project::factory()->create();
        $connection = Connection::factory()->for($project)->withSecret('s')->revoked()->create();

        $this->getJson('/api/v1/grants', [
            'X-Client-Id' => $connection->client_id,
            'X-Client-Secret' => 's',
        ])->assertStatus(403);
    }

    public function test_successful_call_stamps_last_seen_at(): void
    {
        $project = Project::factory()->create();
        $connection = Connection::factory()->for($project)->withSecret('s')->create(['last_seen_at' => null]);

        $this->getJson('/api/v1/grants', [
            'X-Client-Id' => $connection->client_id,
            'X-Client-Secret' => 's',
        ])->assertOk();

        $this->assertNotNull($connection->fresh()->last_seen_at);
    }
}
