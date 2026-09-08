<?php

namespace App\Models;

use Database\Factories\PersonFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Person extends Model
{
    /** @use HasFactory<PersonFactory> */
    use HasFactory;

    protected $fillable = [
        'user_id', 'name', 'email', 'farm', 'department', 'position', 'roles', 'scope', 'active',
    ];

    protected function casts(): array
    {
        return [
            'user_id' => 'integer',
            'roles' => 'array',
            'active' => 'boolean',
        ];
    }

    public function getRouteKeyName(): string
    {
        return 'user_id';
    }

    /** Projects this person is explicitly pointed at (only meaningful when scope = selected). */
    public function projects(): BelongsToMany
    {
        return $this->belongsToMany(Project::class);
    }

    public function hasRole(string $role): bool
    {
        return in_array($role, $this->roles ?? [], true);
    }

    /** @return list<string> display labels for this person's roles, in config order */
    public function roleLabels(): array
    {
        return collect(config('access-hub.roles'))
            ->only($this->roles ?? [])
            ->values()
            ->all();
    }

    /** Apply the admin list filters (farm / department / role), any combination. */
    public function scopeFilter(Builder $query, array $filters): Builder
    {
        return $query
            ->when($filters['farm'] ?? null, fn (Builder $q, $v) => $q->where('farm', $v))
            ->when($filters['department'] ?? null, fn (Builder $q, $v) => $q->where('department', $v))
            ->when($filters['role'] ?? null, fn (Builder $q, $v) => $q->whereJsonContains('roles', $v));
    }

    /**
     * The people a sync would return for a project — the intersection of each
     * person's scope and the project's acceptance setting. Inactive people are
     * included (returned with active: false) so the project can tell "removed"
     * from "never was here".
     */
    public function scopeGrantedTo(Builder $query, Project $project): Builder
    {
        $pointedAtProject = fn (Builder $q) => $q
            ->where('scope', 'selected')
            ->whereHas('projects', fn (Builder $p) => $p->whereKey($project->getKey()));

        if ($project->acceptance === 'explicit') {
            return $query->where($pointedAtProject);
        }

        // open: everyone scoped to all, plus anyone pointed at this project explicitly
        return $query->where(fn (Builder $q) => $q
            ->where('scope', 'all')
            ->orWhere($pointedAtProject));
    }
}
