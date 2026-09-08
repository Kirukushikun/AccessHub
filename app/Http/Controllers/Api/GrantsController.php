<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Connection;
use App\Models\Person;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * GET /api/v1/grants
 *
 * Auth: X-Client-Id / X-Client-Secret headers (see AuthenticateConnection).
 * Returns a flat list, one entry per person that applies to the calling project
 * — including inactive people, so the project can tell "removed" from "never here".
 * Stamps last_seen_at on every successful call.
 */
class GrantsController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        /** @var Connection $connection */
        $connection = $request->attributes->get('connection');

        $connection->forceFill(['last_seen_at' => now()])->save();

        $people = Person::grantedTo($connection->project)
            ->orderBy('name')
            ->get()
            ->map(fn (Person $p) => [
                'user_id' => $p->user_id,
                'name' => $p->name,
                'email' => $p->email,
                'farm' => $p->farm,
                'department' => $p->department,
                'position' => $p->position,
                'roles' => $p->roles,
                'active' => $p->active,
            ]);

        return response()->json([
            'generated_at' => now()->toIso8601String(),
            'people' => $people,
        ]);
    }
}
