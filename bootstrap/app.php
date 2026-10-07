<?php

use App\Http\Middleware\AuthenticateToken;
use App\Http\Middleware\EnsureExpenseAccess;
use App\Http\Middleware\EnsureGroupAccess;
use App\Http\Middleware\EnsureSettlementAccess;
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
    ->withMiddleware(function (Middleware $middleware): void {

        $middleware->alias([
            'auth.token' => AuthenticateToken::class,
            'group.access' => EnsureGroupAccess::class,
            'expense.access' => EnsureExpenseAccess::class,
            'settlement.access' => EnsureSettlementAccess::class,
        ]);

    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })
    ->create();
