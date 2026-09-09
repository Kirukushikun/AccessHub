<?php

namespace Tests\Feature;

use App\Models\AuditEntry;
use App\Models\Person;
use App\Models\Project;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class PeopleTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->create(['active' => true]);
    }

    private function fakeDirectory(array $people): void
    {
        Config::set('services.user_api.endpoint', 'https://directory.test/api/v1/users');
        Config::set('services.user_api.key', 'k');

        Http::fake(['directory.test/*' => Http::response(
            collect($people)->map(fn ($p) => [
                'id' => Crypt::encryptString((string) $p['id']),
                'first_name' => $p['first'],
                'last_name' => $p['last'],
                'email' => $p['email'],
            ])->all()
        )]);
    }

    public function test_multiple_directory_people_can_be_added_at_once(): void
    {
        $this->fakeDirectory([
            ['id' => 10, 'first' => 'Ann', 'last' => 'Aa', 'email' => 'a@x.org'],
            ['id' => 11, 'first' => 'Ben', 'last' => 'Bb', 'email' => 'b@x.org'],
            ['id' => 12, 'first' => 'Cy', 'last' => 'Cc', 'email' => 'c@x.org'],
        ]);

        $this->actingAs($this->admin())->post('/people', [
            'user_ids' => [10, 11, 12],
            'roles' => ['user'],
            'scope' => 'all',
        ])->assertRedirect('/people');

        $this->assertSame(3, Person::count());
        $this->assertSame(3, AuditEntry::where('action', 'person.added')->count());
        $this->assertSame('all', Person::firstWhere('user_id', 10)->scope);
    }

    public function test_ids_not_in_the_directory_are_skipped_not_fatal(): void
    {
        $this->fakeDirectory([['id' => 10, 'first' => 'Ann', 'last' => 'Aa', 'email' => 'a@x.org']]);

        $this->actingAs($this->admin())->post('/people', [
            'user_ids' => [10, 999],
            'roles' => ['user'],
            'scope' => 'all',
        ])->assertRedirect('/people');

        $this->assertSame(1, Person::count());
    }

    public function test_people_list_filters_by_farm_department_and_role(): void
    {
        Person::factory()->roles('vp')->create(['farm' => 'BFC', 'department' => 'Accounting', 'name' => 'Match']);
        Person::factory()->roles('user')->create(['farm' => 'PFC', 'department' => 'Poultry', 'name' => 'Nope']);

        $this->actingAs($this->admin())
            ->get('/people?farm=BFC&department=Accounting&role=vp')
            ->assertOk()
            ->assertSee('Match')
            ->assertDontSee('>Nope<', false);
    }

    public function test_role_filter_matches_any_of_a_persons_roles(): void
    {
        Person::factory()->roles(['manager', 'division_head'])->create(['name' => 'Multi']);

        $this->actingAs($this->admin())->get('/people?role=division_head')
            ->assertOk()->assertSee('Multi');
    }

    public function test_updating_roles_writes_one_audit_entry(): void
    {
        $person = Person::factory()->roles('user')->create(['scope' => 'all']);

        $this->actingAs($this->admin())->put("/people/{$person->user_id}", [
            'roles' => ['division_head', 'manager'],
            'scope' => 'all',
            'active' => '1',
        ])->assertRedirect('/people');

        $this->assertEqualsCanonicalizing(['division_head', 'manager'], $person->fresh()->roles);
        $this->assertSame(1, AuditEntry::where('action', 'person.roles_changed')->count());
    }

    public function test_bulk_scope_points_people_at_a_project_and_flips_scope(): void
    {
        $project = Project::factory()->create(['key' => 'hrms']);
        $a = Person::factory()->create(['scope' => 'all']);
        $b = Person::factory()->create(['scope' => 'all']);

        $this->actingAs($this->admin())->post('/people/bulk-scope', [
            'user_ids' => [$a->user_id, $b->user_id],
            'project_key' => 'hrms',
        ])->assertRedirect('/people');

        $this->assertSame('selected', $a->fresh()->scope);
        $this->assertTrue($b->fresh()->projects->contains($project));
        $this->assertSame(1, AuditEntry::where('action', 'person.bulk_scope_changed')->count());
    }

    public function test_guests_cannot_see_people(): void
    {
        $this->get('/people')->assertRedirect('/login');
    }
}
