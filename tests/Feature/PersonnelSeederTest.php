<?php

namespace Tests\Feature;

use App\Models\AuditEntry;
use App\Models\Person;
use App\Models\Project;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class PersonnelSeederTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Config::set('services.user_api.endpoint', 'https://directory.test/api/v1/users');
        Config::set('services.user_api.key', 'k');

        // A directory that contains the real roster names (see database/data/personnel-directory.md).
        Http::fake(['directory.test/*' => Http::response([
            $this->rec(101, 'Theresa', 'Dizon'),
            $this->rec(102, 'Roddy Ian', 'Gaurano'),
            $this->rec(103, 'Antonio Jr.', 'Acibar'),
            $this->rec(104, 'Michael Adam', 'Trinidad'),
            $this->rec(105, 'Judith', 'Bince'),
            $this->rec(999, 'Nobody', 'Fromroster'),
        ])]);
    }

    private function rec(int $id, string $first, string $last): array
    {
        return [
            'id' => Crypt::encryptString((string) $id),
            'first_name' => $first,
            'last_name' => $last,
            'middle_name' => null,
            'email' => strtolower($first[0].'.'.str_replace(' ', '', $last)).'@bfcgroup.org',
        ];
    }

    public function test_it_wipes_and_repopulates_people_from_the_roster(): void
    {
        // pre-existing data that must be cleared
        $stale = Person::factory()->selectedScope()->create(['user_id' => 55555]);
        $stale->projects()->attach(Project::factory()->create());

        $this->artisan('db:seed', ['--class' => 'PersonnelSeeder', '--force' => true])->assertOk();

        $this->assertDatabaseMissing('people', ['user_id' => 55555]);
        $this->assertSame(0, \DB::table('person_project')->count());

        // matched roster people are in
        $dizon = Person::firstWhere('user_id', 101);
        $this->assertNotNull($dizon);
        $this->assertSame(['vp'], $dizon->roles);
        $this->assertSame('all', $dizon->scope);

        $this->assertSame(['division_head'], Person::firstWhere('user_id', 103)->roles);
        $this->assertSame(['manager'], Person::firstWhere('user_id', 104)->roles); // "IT Manager" -> manager
        $this->assertSame(['manager'], Person::firstWhere('user_id', 105)->roles); // a supervisor -> manager
    }

    public function test_it_records_a_dated_audit_entry(): void
    {
        $this->artisan('db:seed', ['--class' => 'PersonnelSeeder', '--force' => true]);

        $entry = AuditEntry::where('action', 'people.roster_loaded')->latest('created_at')->first();

        $this->assertNotNull($entry);
        $this->assertStringContainsString('As of', $entry->summary);
        $this->assertSame('personnel-seeder', $entry->actor_label);
        $this->assertArrayHasKey('loaded_at', $entry->meta);
        $this->assertContains('DE ASIS, HAILY FLORENCE VERZOLA', $entry->meta['unmatched']);
    }

    public function test_it_aborts_cleanly_when_the_directory_is_unreachable(): void
    {
        Config::set('services.user_api.endpoint', '');
        Person::factory()->create(['user_id' => 1]);

        $this->artisan('db:seed', ['--class' => 'PersonnelSeeder', '--force' => true]);

        // people table untouched
        $this->assertDatabaseHas('people', ['user_id' => 1]);
    }
}
