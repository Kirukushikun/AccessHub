<?php

namespace App\Models;

use Database\Factories\ProjectFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Project extends Model
{
    /** @use HasFactory<ProjectFactory> */
    use HasFactory;

    protected $fillable = ['key', 'name', 'acceptance', 'active'];

    protected function casts(): array
    {
        return ['active' => 'boolean'];
    }

    public function getRouteKeyName(): string
    {
        return 'key';
    }

    public function connections(): HasMany
    {
        return $this->hasMany(Connection::class);
    }

    public function liveConnections(): HasMany
    {
        return $this->connections()->whereNull('revoked_at');
    }

    public function codes(): HasMany
    {
        return $this->hasMany(ConnectionCode::class);
    }

    public function people(): BelongsToMany
    {
        return $this->belongsToMany(Person::class);
    }

    public function acceptanceLabel(): string
    {
        return config('access-hub.acceptance')[$this->acceptance] ?? $this->acceptance;
    }
}
