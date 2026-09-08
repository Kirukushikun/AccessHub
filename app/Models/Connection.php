<?php

namespace App\Models;

use App\Support\AccessHub;
use Database\Factories\ConnectionFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Connection extends Model
{
    /** @use HasFactory<ConnectionFactory> */
    use HasFactory;

    protected $fillable = [
        'project_id', 'environment', 'client_id', 'client_secret_hash', 'domain', 'last_seen_at', 'revoked_at',
    ];

    protected $hidden = ['client_secret_hash'];

    public function getRouteKeyName(): string
    {
        return 'client_id';
    }

    protected function casts(): array
    {
        return [
            'last_seen_at' => 'datetime',
            'revoked_at' => 'datetime',
        ];
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function scopeLive(Builder $query): Builder
    {
        return $query->whereNull('revoked_at');
    }

    public function isRevoked(): bool
    {
        return $this->revoked_at !== null;
    }

    public function isStale(): bool
    {
        return ! $this->isRevoked()
            && ($this->last_seen_at === null
                || $this->last_seen_at->lt(now()->subDays(AccessHub::staleAfterDays())));
    }

    public function status(): string
    {
        return match (true) {
            $this->isRevoked() => 'revoked',
            $this->isStale() => 'stale',
            default => 'live',
        };
    }
}
