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
        // Trust upstream reverse proxies so Laravel can honor forwarded proto / host
        // headers and generate secure asset / script URLs in cloud environments.
        $middleware->trustProxies(at: '*');

        $middleware->alias([
            'auth' => \App\Http\Middleware\Authenticate::class,
            'tenant' => \App\Http\Middleware\SetTenantFromPath::class,
            'tenancy.disabled' => \App\Http\Middleware\TenancyDisabled::class,
            'admin' => \App\Http\Middleware\EnsureAdmin::class,
            'super.admin' => \App\Http\Middleware\EnsureSuperAdmin::class,
            'no-store' => \App\Http\Middleware\PreventCachingSensitivePages::class,
            'renter.session.active' => \App\Http\Middleware\EnsureRenterSessionIsActive::class,
        ]);

        $middleware->prependToPriorityList(
            \Illuminate\Routing\Middleware\SubstituteBindings::class,
            \App\Http\Middleware\SetTenantFromPath::class,
        );
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
