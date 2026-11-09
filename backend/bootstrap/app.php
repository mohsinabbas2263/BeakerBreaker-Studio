<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        // SR-04: role checks run on the server, never in the client.
        $middleware->alias([
            'role' => \App\Http\Middleware\EnsureRole::class,
        ]);

        // SR-02: security headers on every response, HSTS in production.
        $middleware->append(\App\Http\Middleware\SecurityHeaders::class);

        // Behind the TLS proxy (deploy/Caddyfile) the app sees plain HTTP from the proxy;
        // trusting it is what makes request URLs and secure cookies come out as https.
        $middleware->trustProxies(at: '*');
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();
