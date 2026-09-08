<?php

namespace App\Support;

use App\Models\AuditEntry;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

/**
 * Append-only audit trail. Call Audit::record(...) from every mutating action.
 *
 * Actor resolution:
 *  - defaults to the authenticated admin (actor_type "admin", label = email)
 *  - non-web callers (the enroll endpoint) set context first via Audit::actingAs()
 */
class Audit
{
    private static ?array $context = null;

    /** Set the actor for callers with no web session (API, console). */
    public static function actingAs(string $type, ?int $id, string $label): void
    {
        self::$context = ['type' => $type, 'id' => $id, 'label' => $label];
    }

    public static function clearContext(): void
    {
        self::$context = null;
    }

    public static function record(string $action, string $summary, ?Model $subject = null, array $meta = []): AuditEntry
    {
        [$type, $id, $label] = self::resolveActor();

        return AuditEntry::create([
            'actor_type' => $type,
            'actor_id' => $id,
            'actor_label' => $label,
            'action' => $action,
            'subject_type' => $subject ? $subject::class : null,
            'subject_id' => $subject?->getKey(),
            'summary' => $summary,
            'meta' => $meta ?: null,
            'ip' => request()?->ip(),
            'created_at' => now(),
        ]);
    }

    /** @return array{0: string, 1: int|null, 2: string} */
    private static function resolveActor(): array
    {
        if (self::$context !== null) {
            return [self::$context['type'], self::$context['id'], self::$context['label']];
        }

        if ($user = Auth::user()) {
            return ['admin', $user->getKey(), $user->email];
        }

        return ['system', null, 'system'];
    }
}
