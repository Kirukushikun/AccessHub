<?php

namespace App\Http\Middleware;

use App\Models\Connection;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Symfony\Component\HttpFoundation\Response;

/**
 * Client-credentials auth for the sync API. The calling project sends the
 * client_id / client_secret it received at enrollment:
 *
 *   X-Client-Id:     chub_xxxxxxxx
 *   X-Client-Secret: <secret>
 *
 * On success the resolved Connection is attached to the request as "connection".
 */
class AuthenticateConnection
{
    public function handle(Request $request, Closure $next): Response
    {
        $clientId = $request->header('X-Client-Id');
        $secret = $request->header('X-Client-Secret');

        if (! $clientId || ! $secret) {
            return response()->json(['message' => 'Missing client credentials.'], 401);
        }

        $connection = Connection::where('client_id', $clientId)->first();

        if (! $connection || ! Hash::check($secret, $connection->client_secret_hash)) {
            return response()->json(['message' => 'Invalid client credentials.'], 401);
        }

        if ($connection->isRevoked()) {
            return response()->json(['message' => 'This connection has been revoked.'], 403);
        }

        $request->attributes->set('connection', $connection);

        return $next($request);
    }
}
