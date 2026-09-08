<?php

namespace App\Support;

use Illuminate\Support\Collection;

/**
 * Thin, config-backed accessors for the hub's fixed vocabulary and org
 * reference data. Keeps Blade views and components free of config() noise.
 */
class AccessHub
{
    /** @return array<string, string> role key => label */
    public static function roles(): array
    {
        return config('access-hub.roles');
    }

    public static function roleLabel(?string $key): string
    {
        return static::roles()[$key] ?? (string) $key;
    }

    /** @return array<string, string> */
    public static function scopes(): array
    {
        return config('access-hub.scopes');
    }

    public static function scopeLabel(?string $key): string
    {
        return static::scopes()[$key] ?? (string) $key;
    }

    /** @return array<string, string> */
    public static function acceptance(): array
    {
        return config('access-hub.acceptance');
    }

    public static function acceptanceLabel(?string $key): string
    {
        return static::acceptance()[$key] ?? (string) $key;
    }

    /** @return list<string> */
    public static function environments(): array
    {
        return config('access-hub.environments');
    }

    public static function farms(): Collection
    {
        return collect(config('access-hub.farms'));
    }

    public static function departments(): Collection
    {
        return collect(config('access-hub.departments'));
    }

    public static function staleAfterDays(): int
    {
        return (int) config('access-hub.stale_after_days', 14);
    }

    public static function codeTtlMinutes(): int
    {
        return (int) config('access-hub.code_ttl_minutes', 15);
    }
}
