<?php

use App\Http\Middleware\AddSecurityHeaders;
use App\Http\Middleware\EnsureAiAccess;
use App\Http\Middleware\EnsureMerchantSubscription;
use App\Http\Middleware\EnsureModuleAccess;
use App\Http\Middleware\EnsurePlatformAvailable;
use App\Http\Middleware\EnsureSuperAdmin;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->web(append: [AddSecurityHeaders::class, EnsurePlatformAvailable::class]);
        $middleware->alias([
            'super-admin' => EnsureSuperAdmin::class,
            'merchant-subscription' => EnsureMerchantSubscription::class,
            'ai-access' => EnsureAiAccess::class,
            'module' => EnsureModuleAccess::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );
    })->create();
