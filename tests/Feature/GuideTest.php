<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GuideTest extends TestCase
{
    use RefreshDatabase;

    public function test_admins_can_view_the_connection_guide(): void
    {
        $admin = User::factory()->create(['active' => true]);

        $this->actingAs($admin)->get('/guide')
            ->assertOk()
            ->assertSee('project_key')
            ->assertSee('/api/v1/enroll');
    }

    public function test_guests_are_redirected_to_login(): void
    {
        $this->get('/guide')->assertRedirect('/login');
    }
}
