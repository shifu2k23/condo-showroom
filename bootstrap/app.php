<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        channels: __DIR__.'/../routes/channels.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // Trust local reverse proxies (e.g. ngrok) so Laravel reads X-Forwarded-Proto
        // and generates correct https asset / script URLs.
        $middleware->trustProxies(at: ['127.0.0.1', '::1']);

        $middleware->alias([
            'admin' => \App\Http\Middleware\IsAdmin::class,
            'no-store' => \App\Http\Middleware\PreventCachingSensitivePages::class,
            'renter.session.active' => \App\Http\Middleware\EnsureRenterSessionIsActive::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
