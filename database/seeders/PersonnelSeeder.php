<?php

namespace Database\Seeders;

use App\Models\Person;
use App\Services\DirectoryClient;
use App\Services\DirectoryUnavailable;
use App\Support\Audit;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Loads the real personnel roster.
 *
 *   php artisan db:seed --class=PersonnelSeeder --force
 *
 * It reads database/data/personnel-directory.md (the org chart — who is a VP /
 * division head / manager / supervisor), matches each name to a user in the
 * external directory API to get their real user_id, then WIPES the people table
 * and repopulates it. No merge, no overwrite — a clean reload every time.
 *
 * Role mapping:  VP -> vp,  Division Head -> division_head,
 *                Manager -> manager,  Supervisor -> manager
 *
 * Not wired into DatabaseSeeder — it needs the directory API reachable, so run
 * it deliberately after deploy (and again whenever the org chart changes).
 */
class PersonnelSeeder extends Seeder
{
    private const SOURCE = 'personnel-directory.md';

    /** MD department label => hub department (config/access-hub.php). Unlisted => null. */
    private const DEPARTMENTS = [
        'POULTRY' => 'Poultry',
        'SWINE' => 'Swine',
        'FEEDMILL' => 'Feedmill',
        'PURCHASING' => 'Purchasing',
        'GENERAL SERVICES' => 'General Services',
        'HRD' => 'Human Resources',
        'IT & SECURITY' => 'IT and Security Services',
        'SALES AND MARKETING' => 'Sales & Marketing',
        // FOC spans Accounting / Audit / Treasury — resolved from the position text.
    ];

    public function run(DirectoryClient $directory): void
    {
        try {
            $people = $directory->users();
        } catch (DirectoryUnavailable $e) {
            $this->command->error("Cannot seed personnel: {$e->getMessage()}");

            return;
        }

        $roster = $this->readRoster();
        if ($roster->isEmpty()) {
            $this->command->error('No rows parsed from '.self::SOURCE.' — nothing to seed.');

            return;
        }

        $byLastName = $people->groupBy(fn ($u) => $this->key($u['last_name']));

        $matched = collect();
        $unmatched = collect();
        $weak = collect(); // matched on surname alone — the given names didn't line up

        foreach ($roster as $entry) {
            $hit = $this->match($entry['name'], $byLastName);

            if ($hit === null) {
                $unmatched->push($entry['name']);

                continue;
            }

            $user = $hit['user'];

            if ($hit['score'] < 10) {
                $weak->push("{$entry['name']}  →  #{$user['user_id']} {$user['name']} <{$user['email']}>");
            }

            // A person can appear once only; first hit (highest role, VPs first) wins.
            if ($matched->has($user['user_id'])) {
                continue;
            }

            $matched->put($user['user_id'], [
                'user_id' => $user['user_id'],
                'name' => $this->cleanName($user['name']),
                'email' => $user['email'],
                'farm' => null,
                'department' => $this->department($entry['department'], $entry['position']),
                'position' => $entry['position'],
                'roles' => [$entry['role']],
                'scope' => 'all',
                'active' => true,
            ]);
        }

        $this->wipeAndInsert($matched->values());

        $stamp = Carbon::now();
        Audit::actingAs('system', null, 'personnel-seeder');
        Audit::record(
            'people.roster_loaded',
            "Personnel roster reloaded from {$this->sourceLabel()} — {$matched->count()} people ".
            "({$unmatched->count()} unmatched). As of {$stamp->toDayDateTimeString()}.",
            meta: [
                'source' => self::SOURCE,
                'loaded_at' => $stamp->toIso8601String(),
                'matched' => $matched->count(),
                'unmatched' => $unmatched->all(),
                'weak_matches' => $weak->all(),
            ],
        );
        Audit::clearContext();

        $this->report($matched, $unmatched, $weak, $roster->count(), $stamp);
    }

    // ---------------------------------------------------------------------
    // Parsing database/data/personnel-directory.md
    // ---------------------------------------------------------------------

    /** @return Collection<int, array{name: string, role: string, department: ?string, position: ?string}> */
    private function readRoster(): Collection
    {
        $path = database_path('data/'.self::SOURCE);
        if (! is_file($path)) {
            $this->command->error("Missing {$path}");

            return collect();
        }

        $sections = [
            'Vice Presidents' => 'vp',
            'Division Heads' => 'division_head',
            'Managers' => 'manager',
            'Supervisors' => 'manager',
        ];

        $rows = collect();
        $role = null;
        $hasDeptColumn = false;

        foreach (preg_split('/\R/', file_get_contents($path)) as $line) {
            $line = trim($line);

            if (str_starts_with($line, '## ')) {
                $heading = trim(substr($line, 3));
                $role = $sections[$heading] ?? null;
                $hasDeptColumn = $role !== null && $role !== 'vp';

                continue;
            }
            if (str_starts_with($line, '#')) {
                $role = null;

                continue;
            }
            if ($role === null || ! str_starts_with($line, '|')) {
                continue;
            }

            $cells = array_map('trim', explode('|', trim($line, '|')));

            // skip header + separator rows
            if (($cells[0] ?? '') === '#' || str_contains($cells[1] ?? '', '---') || ! is_numeric($cells[0] ?? '')) {
                continue;
            }

            $name = $cells[1] ?? '';
            if ($name === '') {
                continue;
            }

            $rows->push([
                'name' => $name,
                'role' => $role,
                'department' => $hasDeptColumn ? ($cells[2] ?? null) : null,
                'position' => $hasDeptColumn ? ($cells[3] ?? null) : ($cells[2] ?? null),
            ]);
        }

        return $rows;
    }

    // ---------------------------------------------------------------------
    // Name matching
    // ---------------------------------------------------------------------

    /**
     * @param  Collection<string, Collection<int, array>>  $byLastName
     * @return array{user: array, score: int}|null
     */
    private function match(string $rosterName, Collection $byLastName): ?array
    {
        [$last, $firstTokens] = $this->splitName($rosterName);

        $candidates = $byLastName->get($this->key($last), collect());
        if ($candidates->isEmpty()) {
            return null;
        }

        $wanted = collect($firstTokens)->map(fn ($t) => $this->key($t))->filter()->values();

        $scored = $candidates->map(function ($u) use ($wanted) {
            $dirFirst = collect(explode(' ', $this->key($u['first_name'])))->filter()->values();
            $score = 0;

            if ($dirFirst->isNotEmpty() && $dirFirst->every(fn ($t) => $wanted->contains($t))) {
                $score += 10;                                     // every directory first-name token appears
                if ($wanted->take($dirFirst->count())->values()->all() === $dirFirst->all()) {
                    $score += 5;                                  // ...and as a leading prefix
                }
            } elseif ($dirFirst->intersect($wanted)->isNotEmpty()) {
                $score += 4;                                      // at least one given name in common
            }

            if ($wanted->isNotEmpty()
                && Str::substr($this->key($u['first_name']), 0, 1) === Str::substr($wanted->first(), 0, 1)) {
                $score += 2;                                      // same first initial
            }

            if (str_ends_with(strtolower((string) $u['email']), '@bfcgroup.org')) {
                $score += 1;                                      // prefer the work address
            }

            return ['user' => $u, 'score' => $score];
        });

        $best = $scored->sortByDesc('score')->first();

        // Surname must be backed by a real given-name signal — a lone same-surname
        // record with a different first name is NOT a match (that person just isn't
        // in the directory). Threshold 3 = "shared given name" or "initial + work email".
        return ($best && $best['score'] >= 3) ? $best : null;
    }

    /** Title-case a name the directory stored SHOUTING; leave already-cased names alone. */
    private function cleanName(string $name): string
    {
        return $name === Str::upper($name) ? Str::title(Str::lower($name)) : $name;
    }

    /**
     * "TRINIDAD, MICHAEL ADAM Estabillo" => ['TRINIDAD', ['MICHAEL','ADAM','Estabillo']]
     * "FABELLA ALVIN GARON"              => ['FABELLA', ['ALVIN','GARON']]
     *
     * @return array{0: string, 1: list<string>}
     */
    private function splitName(string $raw): array
    {
        $raw = trim(preg_replace('/\s+/', ' ', $raw));

        if (str_contains($raw, ',')) {
            [$last, $rest] = explode(',', $raw, 2);
            $rest = str_replace(',', ' ', $rest); // stray extra commas
        } else {
            $parts = explode(' ', $raw, 2);
            $last = $parts[0];
            $rest = $parts[1] ?? '';
        }

        $tokens = array_values(array_filter(
            explode(' ', trim($rest)),
            fn ($t) => $t !== '' && ! in_array(strtoupper(rtrim($t, '.')), ['JR', 'SR', 'II', 'III', 'IV'], true),
        ));

        return [trim($last), $tokens];
    }

    /** Uppercase, drop accents/punctuation/suffixes — a loose comparison key. */
    private function key(string $value): string
    {
        $value = Str::ascii($value);
        $value = preg_replace('/[^A-Za-z0-9 ]/', ' ', $value);
        $value = preg_replace('/\b(JR|SR|II|III|IV)\b/i', '', $value);

        return trim(preg_replace('/\s+/', ' ', Str::upper($value)));
    }

    private function department(?string $mdLabel, ?string $position): ?string
    {
        $mdLabel = strtoupper(trim((string) $mdLabel));

        if (isset(self::DEPARTMENTS[$mdLabel])) {
            return self::DEPARTMENTS[$mdLabel];
        }

        // FOC = Finance: Accounting / Audit / Treasury — pick from the position text.
        $position = strtolower((string) $position);
        foreach (['treasury' => 'Treasury', 'audit' => 'Audit', 'account' => 'Accounting'] as $needle => $dept) {
            if (str_contains($position, $needle)) {
                return $dept;
            }
        }

        return null;
    }

    // ---------------------------------------------------------------------

    /** @param  Collection<int, array>  $people */
    private function wipeAndInsert(Collection $people): void
    {
        DB::transaction(function () use ($people) {
            // Plain DELETE (not TRUNCATE) so it stays inside the transaction.
            // person_project first, then people (its FK).
            DB::table('person_project')->delete();
            DB::table('people')->delete();

            $now = now();
            $people
                ->map(fn (array $p) => [...$p, 'roles' => json_encode($p['roles']), 'created_at' => $now, 'updated_at' => $now])
                ->chunk(200)
                ->each(fn ($chunk) => DB::table('people')->insert($chunk->all()));
        });
    }

    private function sourceLabel(): string
    {
        $path = database_path('data/'.self::SOURCE);
        $line = collect(preg_split('/\R/', (string) file_get_contents($path)))
            ->first(fn ($l) => str_starts_with(trim($l), 'Source:'));

        return $line ? trim(Str::of($line)->after('Source:')->replace('`', '')->toString()) : self::SOURCE;
    }

    private function report(Collection $matched, Collection $unmatched, Collection $weak, int $total, Carbon $stamp): void
    {
        $this->command->newLine();
        $this->command->info("Personnel roster — as of {$stamp->toDayDateTimeString()}");
        $this->command->line("  source:    {$this->sourceLabel()}");
        $this->command->line("  in file:   {$total}");
        $this->command->line("  seeded:    {$matched->count()}");

        $byRole = $matched->groupBy(fn ($p) => $p['roles'][0])->map->count();
        foreach (['vp' => 'VP', 'division_head' => 'Division head', 'manager' => 'Manager / supervisor'] as $key => $label) {
            $this->command->line(sprintf('    %-22s %d', $label, $byRole[$key] ?? 0));
        }

        if ($weak->isNotEmpty()) {
            $this->command->newLine();
            $this->command->warn("  Low-confidence matches ({$weak->count()}) — only the surname lined up. Verify these are the right person:");
            $weak->each(fn ($w) => $this->command->line("    - {$w}"));
        }

        if ($unmatched->isNotEmpty()) {
            $this->command->newLine();
            $this->command->warn("  NOT found in the directory ({$unmatched->count()}) — add these by hand or fix the spelling in the file:");
            $unmatched->each(fn ($n) => $this->command->line("    - {$n}"));
        }
        $this->command->newLine();
    }
}
