<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__ . '/../routes/web.php',
        commands: __DIR__ . '/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // Trust upstream proxy headers so URL signature validation uses the
        // original host / scheme when the app runs behind a load balancer.
        $middleware->trustProxies(at: '*');
        $middleware->validateCsrfTokens(except: [
            'f/*/sync',
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
