<?php

namespace Tests\Feature;

use App\Models\AuditEntry;
use App\Models\Connection;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ConnectionTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->create(['active' => true]);
    }

    public function test_revoking_sets_revoked_at_and_audits(): void
    {
        $connection = Connection::factory()->create(['revoked_at' => null]);

        $this->actingAs($this->admin())
            ->post("/connections/{$connection->client_id}/revoke")
            ->assertRedirect();

        $this->assertNotNull($connection->fresh()->revoked_at);
        $this->assertSame(1, AuditEntry::where('action', 'connection.revoked')->count());
    }

    public function test_regenerating_changes_the_secret_hash(): void
    {
        $connection = Connection::factory()->create(['revoked_at' => null]);
        $before = $connection->client_secret_hash;

        $this->actingAs($this->admin())
            ->post("/connections/{$connection->client_id}/regenerate")
            ->assertRedirect()
            ->assertSessionHas('connection_code');

        $this->assertNotSame($before, $connection->fresh()->client_secret_hash);
        $this->assertSame(1, AuditEntry::where('action', 'connection.secret_rotated')->count());
    }

    public function test_revoked_connection_cannot_be_regenerated(): void
    {
        $connection = Connection::factory()->revoked()->create();

        $this->actingAs($this->admin())
            ->post("/connections/{$connection->client_id}/regenerate")
            ->assertSessionHas('info');
    }
}
