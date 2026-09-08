<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ConnectionCode;
use App\Models\Project;
use App\Support\AccessHub;
use App\Support\Audit;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

/**
 * POST /api/v1/enroll
 *
 * Body: code, project_key, environment.
 * No auth — the code is the auth. Rate limited. Logs source IP.
 * Returns client_id + client_secret ONCE.
 */
class EnrollController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        $data = $request->validate([
            'code' => ['required', 'string'],
            'project_key' => ['required', 'string'],
            'environment' => ['required', Rule::in(AccessHub::environments())],
        ]);

        $project = Project::where('key', $data['project_key'])->where('active', true)->first();

        if (! $project) {
            return response()->json(['message' => 'Unknown or inactive project.'], 404);
        }

        $code = $project->codes()->usable()->get()
            ->first(fn (ConnectionCode $c) => Hash::check($data['code'], $c->code_hash));

        if (! $code) {
            Log::warning('Enroll rejected: bad code', [
                'project' => $project->key,
                'ip' => $request->ip(),
            ]);

            return response()->json(['message' => 'Invalid or expired code.'], 422);
        }

        $code->burn();

        $clientId = 'chub_'.Str::lower(Str::random(24));
        $clientSecret = Str::random(48);

        // One row per (project, environment): re-enrolling replaces the secret
        // and clears any prior revocation.
        $connection = $project->connections()->updateOrCreate(
            ['environment' => $data['environment']],
            [
                'client_id' => $clientId,
                'client_secret_hash' => Hash::make($clientSecret),
                'revoked_at' => null,
                'last_seen_at' => null,
            ],
        );

        Audit::actingAs('project', $connection->id, $project->key);
        Audit::record('connection.enrolled', "{$project->name} · {$data['environment']}", $project, [
            'environment' => $data['environment'],
            'ip' => $request->ip(),
        ]);
        Audit::clearContext();

        return response()->json([
            'client_id' => $clientId,
            'client_secret' => $clientSecret,
        ]);
    }
}
