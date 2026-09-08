<?php

namespace App\Http\Controllers;

use App\Models\Person;
use App\Models\Project;
use App\Support\AccessHub;
use App\Support\Audit;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ProjectController extends Controller
{
    public function index(): View
    {
        return view('projects.index', [
            'projects' => Project::query()
                ->withCount(['connections as live_connections_count' => fn ($q) => $q->whereNull('revoked_at')])
                ->orderBy('name')
                ->get(),
            'acceptance' => AccessHub::acceptance(),
        ]);
    }

    public function create(): View
    {
        return view('projects.create', ['acceptance' => AccessHub::acceptance()]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'key' => ['required', 'string', 'max:64', 'regex:/^[a-z0-9-]+$/', Rule::unique('projects', 'key')],
            'acceptance' => ['required', Rule::in(array_keys(AccessHub::acceptance()))],
        ]);

        $project = Project::create($data + ['active' => true]);

        Audit::record('project.registered', "{$project->name} ({$project->key})", $project, [
            'acceptance' => $project->acceptance,
        ]);

        return redirect()->route('projects.show', $project)->with('success', "{$project->name} registered.");
    }

    public function show(Project $project): View
    {
        return view('projects.show', [
            'project' => $project,
            'connections' => $project->connections()->get(),
            'environments' => AccessHub::environments(),
            'people' => Person::grantedTo($project)->orderBy('name')->get(),
            'acceptance' => AccessHub::acceptance(),
            'usableCodes' => $project->codes()->usable()->count(),
        ]);
    }

    public function edit(Project $project): View
    {
        return view('projects.edit', [
            'project' => $project,
            'acceptance' => AccessHub::acceptance(),
        ]);
    }

    public function update(Request $request, Project $project): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'key' => ['required', 'string', 'max:64', 'regex:/^[a-z0-9-]+$/', Rule::unique('projects', 'key')->ignore($project)],
            'acceptance' => ['required', Rule::in(array_keys(AccessHub::acceptance()))],
            'active' => ['nullable', 'boolean'],
        ]);

        $before = $project->only(['name', 'key', 'acceptance', 'active']);
        $project->fill($data + ['active' => $request->boolean('active')])->save();

        $changed = collect($before)->reject(fn ($v, $k) => $v === $project->$k)->keys();
        if ($changed->isNotEmpty()) {
            Audit::record('project.updated',
                "{$project->name}: ".$changed->implode(', ').' updated', $project,
                ['before' => $before]);
        }

        return redirect()->route('projects.show', $project)->with('success', 'Project updated.');
    }

    public function generateCode(Project $project): RedirectResponse
    {
        // HUB-XXXX-XXXX, Crockford-ish alphabet (no I/L/O/U), high entropy.
        $alphabet = '0123456789ABCDEFGHJKMNPQRSTVWXYZ';
        $chunk = fn () => collect(range(1, 4))->map(fn () => $alphabet[random_int(0, 30)])->implode('');
        $code = 'HUB-'.$chunk().'-'.$chunk();

        $project->codes()->create([
            'code_hash' => Hash::make($code),
            'expires_at' => now()->addMinutes(AccessHub::codeTtlMinutes()),
            'created_by' => auth()->id(),
        ]);

        Audit::record('connection.code_issued', "{$project->name}", $project, [
            'expires_in_minutes' => AccessHub::codeTtlMinutes(),
        ]);

        return back()->with([
            'success' => 'Connection code generated. It is shown once and expires in '
                .AccessHub::codeTtlMinutes().' minutes.',
            'connection_code' => $code,
        ]);
    }
}
