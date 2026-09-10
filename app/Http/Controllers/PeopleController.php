<?php

namespace App\Http\Controllers;

use App\Models\Person;
use App\Models\Project;
use App\Services\DirectoryClient;
use App\Services\DirectoryUnavailable;
use App\Support\AccessHub;
use App\Support\Audit;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class PeopleController extends Controller
{
    public function index(Request $request): View
    {
        $filters = $request->only(['farm', 'department', 'role']);

        $people = Person::query()
            ->with('projects:id,key,name')
            ->filter($filters)
            ->orderBy('name')
            ->paginate(20)
            ->withQueryString();

        return view('people.index', [
            'people' => $people,
            'roles' => AccessHub::roles(),
            'farms' => AccessHub::farms(),
            'departments' => AccessHub::departments(),
            'projects' => Project::where('active', true)->orderBy('name')->get(),
            'filters' => $filters,
        ]);
    }

    public function create(DirectoryClient $directory): View
    {
        $directoryError = null;
        $candidates = collect();

        try {
            $existing = Person::pluck('user_id')->all();
            $candidates = $directory->users()
                ->reject(fn (array $u) => in_array($u['user_id'], $existing, true))
                ->values();
        } catch (DirectoryUnavailable $e) {
            $directoryError = $e->getMessage();
        }

        return view('people.create', [
            'directory' => $candidates,
            'directoryError' => $directoryError,
            'roles' => AccessHub::roles(),
            'scopes' => AccessHub::scopes(),
            'projects' => Project::where('active', true)->orderBy('name')->get(),
        ]);
    }

    public function store(Request $request, DirectoryClient $directory): RedirectResponse
    {
        $data = $request->validate([
            'user_ids' => ['required', 'array', 'min:1'],
            'user_ids.*' => ['integer', 'distinct', Rule::unique('people', 'user_id')],
            'roles' => ['required', 'array', 'min:1'],
            'roles.*' => [Rule::in(array_keys(AccessHub::roles()))],
            'scope' => ['required', Rule::in(array_keys(AccessHub::scopes()))],
            'projects' => ['array'],
            'projects.*' => [Rule::exists('projects', 'key')],
        ]);

        // Never trust the ids from the client — resolve name/email from the directory.
        try {
            $entries = $directory->users()->keyBy('user_id');
        } catch (DirectoryUnavailable $e) {
            return back()->withInput()->withErrors(['user_ids' => $e->getMessage()]);
        }

        $wanted = collect($data['user_ids']);
        $found = $wanted->filter(fn ($id) => $entries->has($id));

        if ($found->isEmpty()) {
            return back()->withInput()->withErrors(['user_ids' => 'None of the selected people are in the directory.']);
        }

        $projectIds = $data['scope'] === 'selected'
            ? Project::whereIn('key', $data['projects'] ?? [])->pluck('id')
            : collect();

        $roles = array_values(array_intersect(array_keys(AccessHub::roles()), $data['roles']));

        $created = DB::transaction(function () use ($found, $entries, $data, $projectIds, $roles) {
            return $found->map(function ($id) use ($entries, $data, $projectIds, $roles) {
                $entry = $entries->get($id);

                $person = Person::create([
                    'user_id' => $entry['user_id'],
                    'name' => $entry['name'],
                    'email' => $entry['email'],
                    'roles' => $roles,
                    'scope' => $data['scope'],
                    'active' => true,
                ]);

                if ($projectIds->isNotEmpty()) {
                    $person->projects()->sync($projectIds);
                }

                Audit::record('person.added', "{$person->name} added as ".implode(', ', $person->roleLabels()), $person, [
                    'roles' => $person->roles,
                    'scope' => $person->scope,
                ]);

                return $person;
            });
        });

        $missing = $wanted->count() - $found->count();
        $message = $created->count() === 1
            ? "{$created->first()->name} added."
            : "{$created->count()} people added.";
        if ($missing > 0) {
            $message .= " {$missing} skipped (not found in the directory).";
        }

        return redirect()->route('people.index')->with('success', $message);
    }

    public function edit(Person $person): View
    {
        $person->load('projects:id,key,name');

        return view('people.edit', [
            'person' => $person,
            'roles' => AccessHub::roles(),
            'scopes' => AccessHub::scopes(),
            'farms' => AccessHub::farms(),
            'departments' => AccessHub::departments(),
            'projects' => Project::where('active', true)->orderBy('name')->get(),
            'selectedProjectKeys' => $person->projects->pluck('key')->all(),
        ]);
    }

    public function update(Request $request, Person $person): RedirectResponse
    {
        $data = $request->validate([
            'roles' => ['required', 'array', 'min:1'],
            'roles.*' => [Rule::in(array_keys(AccessHub::roles()))],
            'scope' => ['required', Rule::in(array_keys(AccessHub::scopes()))],
            'projects' => ['array'],
            'projects.*' => [Rule::exists('projects', 'key')],
            'farm' => ['nullable', 'string', 'max:255'],
            'department' => ['nullable', 'string', 'max:255'],
            'position' => ['nullable', 'string', 'max:255'],
            'active' => ['nullable', 'boolean'],
        ]);

        $before = $person->only(['roles', 'scope', 'farm', 'department', 'position', 'active']);

        $person->fill([
            'roles' => array_values(array_intersect(array_keys(AccessHub::roles()), $data['roles'])),
            'scope' => $data['scope'],
            'farm' => $data['farm'] ?? null,
            'department' => $data['department'] ?? null,
            'position' => $data['position'] ?? null,
            'active' => $request->boolean('active'),
        ])->save();

        $person->projects()->sync(
            $data['scope'] === 'selected'
                ? Project::whereIn('key', $data['projects'] ?? [])->pluck('id')
                : []
        );

        $this->auditChanges($person, $before);

        return redirect()->route('people.index')->with('success', "{$person->name} updated.");
    }

    public function bulkScope(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'user_ids' => ['required', 'array', 'min:1'],
            'user_ids.*' => ['integer'],
            'project_key' => ['required', Rule::exists('projects', 'key')],
        ]);

        $project = Project::where('key', $data['project_key'])->firstOrFail();
        $people = Person::whereIn('user_id', $data['user_ids'])->get();

        DB::transaction(function () use ($people, $project) {
            foreach ($people as $person) {
                $person->update(['scope' => 'selected']);
                $person->projects()->syncWithoutDetaching([$project->id]);
            }
        });

        Audit::record(
            'person.bulk_scope_changed',
            "{$people->count()} people pointed at {$project->name}",
            $project,
            ['user_ids' => $people->pluck('user_id')->all()],
        );

        return redirect()->route('people.index')
            ->with('success', "{$people->count()} people pointed at {$project->name}.");
    }

    public function bulkIdentity(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'user_ids' => ['required', 'array', 'min:1'],
            'user_ids.*' => ['integer'],
            'apply_farm' => ['nullable', 'boolean'],
            'farm' => ['nullable', 'string', Rule::in(AccessHub::farms()->all())],
            'apply_department' => ['nullable', 'boolean'],
            'department' => ['nullable', 'string', Rule::in(AccessHub::departments()->all())],
        ]);

        $applyFarm = $request->boolean('apply_farm');
        $applyDepartment = $request->boolean('apply_department');

        if (! $applyFarm && ! $applyDepartment) {
            return back()->withErrors(['farm' => 'Tick Farm and/or Department to say what to change.']);
        }

        $updates = [];
        if ($applyFarm) {
            $updates['farm'] = $data['farm'] ?? null;
        }
        if ($applyDepartment) {
            $updates['department'] = $data['department'] ?? null;
        }

        $people = Person::whereIn('user_id', $data['user_ids'])->get();
        Person::whereIn('user_id', $data['user_ids'])->update($updates);

        $summary = collect($updates)
            ->map(fn ($value, $field) => ucfirst($field).' → '.($value ?? '(cleared)'))
            ->implode(', ');

        Audit::record(
            'person.bulk_identity_updated',
            "{$people->count()} people: {$summary}",
            meta: ['user_ids' => $people->pluck('user_id')->all(), 'updates' => $updates],
        );

        return redirect()->route('people.index')
            ->with('success', "{$people->count()} people updated: {$summary}.");
    }

    private function auditChanges(Person $person, array $before): void
    {
        $rolesBefore = $before['roles'] ?? [];
        if (array_values($rolesBefore) !== array_values($person->roles ?? [])) {
            Audit::record('person.roles_changed',
                "{$person->name}: roles now ".(implode(', ', $person->roleLabels()) ?: '—'),
                $person, ['from' => $rolesBefore, 'to' => $person->roles]);
        }

        if ($before['scope'] !== $person->scope) {
            Audit::record('person.scope_changed',
                "{$person->name}: {$before['scope']} → {$person->scope}",
                $person, ['from' => $before['scope'], 'to' => $person->scope]);
        }

        if ((bool) $before['active'] !== $person->active) {
            Audit::record($person->active ? 'person.reactivated' : 'person.deactivated',
                $person->name, $person);
        }

        $identity = array_filter([
            'farm' => $before['farm'] !== $person->farm,
            'department' => $before['department'] !== $person->department,
            'position' => $before['position'] !== $person->position,
        ]);

        if ($identity !== []) {
            Audit::record('person.identity_updated',
                "{$person->name}: ".implode(', ', array_keys($identity)).' updated',
                $person);
        }
    }
}
