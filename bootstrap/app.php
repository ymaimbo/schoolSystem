<?php
// bootstrap/app.php

use App\Http\Middleware\EnsureUserHasRole;
use App\Http\Middleware\EnforceSchoolBillingAccess;
use App\Http\Middleware\ResolveSchool;
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
        $middleware->alias([
            'role' => EnsureUserHasRole::class,

            // keep both aliases for compatibility with older/newer route usage
            'resolve.school' => ResolveSchool::class,
            'resolve_school' => ResolveSchool::class,

            // school-level billing role gate
            'billing.access' => EnforceSchoolBillingAccess::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();