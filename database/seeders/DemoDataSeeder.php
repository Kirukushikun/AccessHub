<?php

namespace Database\Seeders;

use App\Models\AuditEntry;
use App\Models\Connection;
use App\Models\Person;
use App\Models\Project;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * Realistic sample data for local development and demos. Never runs in production.
 */
class DemoDataSeeder extends Seeder
{
    public function run(): void
    {
        $projects = collect([
            ['key' => 'hrms', 'name' => 'HR Management System', 'acceptance' => 'open', 'active' => true],
            ['key' => 'payroll', 'name' => 'Payroll', 'acceptance' => 'explicit', 'active' => true],
            ['key' => 'inventory', 'name' => 'Inventory Tracker', 'acceptance' => 'open', 'active' => true],
            ['key' => 'gatepass', 'name' => 'Gate Pass', 'acceptance' => 'open', 'active' => false],
        ])->mapWithKeys(fn ($p) => [$p['key'] => Project::create($p)]);

        $people = [
            [412, 'Maria Santos', 'm.santos@example.org', 'BFC', 'Accounting', 'Senior Accountant', ['division_head', 'requestor'], 'all', true, []],
            [87, 'Juan Cruz', 'j.cruz@example.org', 'PFC', 'Poultry', null, ['requestor'], 'all', true, []],
            [5, 'Elena Rodriguez', 'e.rodriguez@example.org', null, 'Treasury', 'VP Finance', ['vp', 'division_head'], 'selected', true, ['hrms', 'payroll']],
            [233, 'David Lim', 'd.lim@example.org', 'BFC', 'IT and Security Services', 'Systems Administrator', ['user'], 'all', true, []],
            [198, 'Grace Tan', 'g.tan@example.org', 'BROOKDALE', 'Human Resources', 'HR Manager', ['requestor'], 'all', true, []],
            [341, 'Robert Cruz', 'r.cruz@example.org', 'PFC', 'Swine', 'Farm Supervisor', ['user'], 'all', false, []],
            [76, 'Anna Reyes', 'a.reyes@example.org', null, 'Audit', 'Chief Audit Executive', ['vp'], 'all', true, []],
            [419, 'Michael Ong', 'm.ong@example.org', 'FEEDMILL', 'Feedmill', 'Bookkeeper', ['user'], 'selected', true, ['payroll']],
            [501, 'Sarah Villanueva', 's.villanueva@example.org', 'HATCHERY', 'General Services', 'Admin Officer', ['user'], 'all', true, []],
            [288, 'Omar Haddad', 'o.haddad@example.org', 'BFC-IRAQ', 'Purchasing', 'Procurement Lead', ['requestor'], 'all', true, []],
            [634, 'Liza Mercado', 'l.mercado@example.org', 'RH/BBGC', 'Sales & Marketing', 'Regional Sales Manager', ['division_head'], 'all', true, []],
        ];

        foreach ($people as [$id, $name, $email, $farm, $dept, $position, $roles, $scope, $active, $projectKeys]) {
            $person = Person::create([
                'user_id' => $id,
                'name' => $name,
                'email' => $email,
                'farm' => $farm,
                'department' => $dept,
                'position' => $position,
                'roles' => $roles,
                'scope' => $scope,
                'active' => $active,
            ]);

            if ($projectKeys) {
                $person->projects()->sync($projects->only($projectKeys)->pluck('id'));
            }
        }

        $connections = [
            ['hrms', 'production', now()->subMinutes(12), null],
            ['hrms', 'staging', now()->subDays(3), null],
            ['payroll', 'production', now()->subHours(5), null],
            ['inventory', 'production', now()->subDays(21), null],
            ['inventory', 'local', now()->subMonths(2), now()->subMonth()],
        ];

        foreach ($connections as [$key, $env, $seen, $revoked]) {
            Connection::create([
                'project_id' => $projects[$key]->id,
                'environment' => $env,
                'client_id' => 'chub_'.Str::lower(Str::random(24)),
                'client_secret_hash' => Hash::make(Str::random(32)),
                'last_seen_at' => $seen,
                'revoked_at' => $revoked,
            ]);
        }

        $audit = [
            [now()->subMinutes(8), 'admin_it@bfcgroup.org', 'person.roles_changed', 'Maria Santos: roles now Division head, Requestor'],
            [now()->subHours(2), 'admin_it@bfcgroup.org', 'connection.code_issued', 'Payroll'],
            [now()->subHours(6), 'admin_it@bfcgroup.org', 'person.scope_changed', 'Michael Ong: all → selected'],
            [now()->subDay(), 'j.delacruz@bfcgroup.org', 'project.registered', 'Inventory Tracker (inventory)'],
            [now()->subDays(2), 'system', 'connection.enrolled', 'HR Management System · production'],
            [now()->subDays(2), 'admin_it@bfcgroup.org', 'person.deactivated', 'Robert Cruz'],
            [now()->subDays(4), 'admin_it@bfcgroup.org', 'connection.revoked', 'Inventory Tracker · local'],
        ];

        foreach ($audit as [$at, $actor, $action, $summary]) {
            AuditEntry::create([
                'actor_type' => $actor === 'system' ? 'system' : 'admin',
                'actor_label' => $actor,
                'action' => $action,
                'summary' => $summary,
                'ip' => '10.0.4.12',
                'created_at' => $at,
            ]);
        }
    }
}
