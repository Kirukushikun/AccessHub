<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\URL;
use Symfony\Component\HttpFoundation\Response;

/**
 * The hub always sits behind HTTPS (Cloudflare / a reverse proxy), but the
 * origin connection is plain HTTP. Without this, Laravel can decide a request
 * is "http", store an http previous-URL, and emit an `http://` redirect after
 * login — at which point the browser drops the `Secure` session cookie and the
 * user bounces back to /login forever.
 *
 * Prepended to the global stack so it runs before TrustProxies / StartSession:
 * every production request is pinned to https end to end.
 */
class PreferHttps
{
    public function handle(Request $request, Closure $next): Response
    {
        if (app()->environment('production')) {
            $request->headers->set('X-Forwarded-Proto', 'https');
            $request->server->set('HTTPS', 'on');

            URL::forceScheme('https');
            URL::forceRootUrl(config('app.url'));
        }

        return $next($request);
    }
}
