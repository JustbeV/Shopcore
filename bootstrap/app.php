<?php

declare(strict_types=1);

use App\Http\Middleware\HandleInertiaRequests;
use App\Http\Middleware\IdentifyTenant;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
        then: function () {
            Route::middleware('api')
                ->prefix('api')
                ->group(base_path('Modules/Identity/routes/api.php'));
        },
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // Sanctum's SPA cookie authentication
        $middleware->statefulApi();

        // Append tenant identification & Inertia props to all web requests
        $middleware->web(append: [
            IdentifyTenant::class,
            HandleInertiaRequests::class,
        ]);

        // Route middleware aliases
        $middleware->alias([
            'tenant' => IdentifyTenant::class,
            'tenant.active' => \App\Http\Middleware\EnsureStoreIsActive::class,
            'tenant.published' => \App\Http\Middleware\EnsureStorePublished::class,
            'tenant.fromRoute' => \App\Http\Middleware\BindTenantFromRouteStore::class,
        ]);

        // Trust proxies for host resolution behind Docker/Nginx/Cloudflare
        $middleware->trustProxies(at: '*');
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*'),
        );
    })->create();