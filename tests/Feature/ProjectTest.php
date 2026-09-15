<?php

namespace Tests\Feature;

use App\Models\AuditEntry;
use App\Models\Project;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProjectTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->create(['active' => true]);
    }

    public function test_generating_a_code_requires_an_environment(): void
    {
        $project = Project::factory()->create(['key' => 'hrms']);

        $this->actingAs($this->admin())
            ->post('/projects/hrms/connection-code', [])
            ->assertSessionHasErrors('environment');

        $this->assertSame(0, $project->codes()->count());
    }

    public function test_generating_a_code_stores_the_environment_it_was_issued_for(): void
    {
        $project = Project::factory()->create(['key' => 'hrms']);

        $this->actingAs($this->admin())
            ->post('/projects/hrms/connection-code', ['environment' => 'staging'])
            ->assertRedirect()
            ->assertSessionHas('connection_code');

        $code = $project->codes()->first();
        $this->assertNotNull($code);
        $this->assertSame('staging', $code->environment);
        $this->assertSame(1, AuditEntry::where('action', 'connection.code_issued')->count());
    }

    public function test_generating_a_code_rejects_an_unknown_environment(): void
    {
        $project = Project::factory()->create(['key' => 'hrms']);

        $this->actingAs($this->admin())
            ->post('/projects/hrms/connection-code', ['environment' => 'sandbox'])
            ->assertSessionHasErrors('environment');

        $this->assertSame(0, $project->codes()->count());
    }
}
