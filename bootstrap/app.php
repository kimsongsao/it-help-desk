<?php

use App\Http\Middleware\EnsurePlanFeature;
use App\Http\Middleware\EnsureUserIsActive;
use App\Http\Middleware\IdentifyOrganization;
use App\Http\Middleware\SetLocale;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Routing\Middleware\SubstituteBindings;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->web(append: [SetLocale::class]);
        $middleware->api(append: [SetLocale::class]);

        $middleware->alias([
            'active' => EnsureUserIsActive::class,
            'tenant' => IdentifyOrganization::class,
            'feature' => EnsurePlanFeature::class,
        ]);

        $middleware->prependToPriorityList(before: SubstituteBindings::class, prepend: IdentifyOrganization::class);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );
    })->create();
