<?php

use App\Http\Middleware\AuthenticateConnection;
use App\Http\Middleware\EnsureActiveAdmin;
use App\Http\Middleware\PreferHttps;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
        apiPrefix: 'api',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // Behind the server's reverse proxy — trust its forwarded headers so
        // HTTPS detection and request IPs (audit log, login lockout) are correct.
        $middleware->trustProxies(at: '*');

        // Pin every production request to https end to end (see the class docblock)
        // — must run before TrustProxies / StartSession, hence prepend.
        $middleware->prepend(PreferHttps::class);

        $middleware->alias([
            'admin' => EnsureActiveAdmin::class,
            'connection.auth' => AuthenticateConnection::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );
    })->create();
