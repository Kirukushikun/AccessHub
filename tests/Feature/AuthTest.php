<?php

namespace Tests\Feature;

use App\Models\AccessLog;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_is_redirected_to_login(): void
    {
        $this->get('/')->assertRedirect('/login');
    }

    public function test_admin_can_sign_in(): void
    {
        $user = User::factory()->create(['email' => 'a@hub.test', 'password' => Hash::make('secret123'), 'active' => true]);

        $this->post('/login', ['email' => 'a@hub.test', 'password' => 'secret123'])
            ->assertRedirect(route('dashboard'));

        $this->assertAuthenticatedAs($user);
        $this->assertDatabaseHas('access_logs', ['email' => 'a@hub.test', 'successful' => true]);
    }

    public function test_three_failures_lock_the_account(): void
    {
        User::factory()->create(['email' => 'a@hub.test', 'password' => Hash::make('secret123'), 'active' => true]);

        foreach (range(1, 3) as $i) {
            $this->post('/login', ['email' => 'a@hub.test', 'password' => 'wrong']);
        }

        // Even the correct password is now refused.
        $this->post('/login', ['email' => 'a@hub.test', 'password' => 'secret123'])
            ->assertSessionHasErrors('email');
        $this->assertGuest();
        $this->assertSame(3, AccessLog::where('successful', false)->count());
    }

    public function test_deactivated_admin_cannot_sign_in(): void
    {
        User::factory()->create(['email' => 'x@hub.test', 'password' => Hash::make('secret123'), 'active' => false]);

        $this->post('/login', ['email' => 'x@hub.test', 'password' => 'secret123'])
            ->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    public function test_deactivated_admin_is_bounced_mid_session(): void
    {
        $user = User::factory()->create(['active' => true]);
        $this->actingAs($user)->get('/people')->assertOk();

        $user->update(['active' => false]);

        $this->actingAs($user)->get('/people')->assertRedirect('/login');
    }

    public function test_admin_cannot_deactivate_their_own_account(): void
    {
        $user = User::factory()->create(['active' => true]);

        $this->actingAs($user)->put("/admins/{$user->id}", ['name' => $user->name, 'active' => '0'])
            ->assertSessionHasErrors('active');

        $this->assertTrue($user->fresh()->active);
    }

    public function test_admin_can_change_their_own_password(): void
    {
        $user = User::factory()->create(['password' => Hash::make('old-password-1')]);

        $this->actingAs($user)->put('/account/password', [
            'current_password' => 'old-password-1',
            'password' => 'brand-new-password-2',
            'password_confirmation' => 'brand-new-password-2',
        ])->assertSessionHasNoErrors();

        $this->assertTrue(Hash::check('brand-new-password-2', $user->fresh()->password));
    }

    public function test_password_change_requires_the_correct_current_password(): void
    {
        $user = User::factory()->create(['password' => Hash::make('old-password-1')]);

        $this->actingAs($user)->put('/account/password', [
            'current_password' => 'wrong',
            'password' => 'brand-new-password-2',
            'password_confirmation' => 'brand-new-password-2',
        ])->assertSessionHasErrors('current_password');
    }
}
