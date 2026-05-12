<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'admin.panel' => \App\Http\Middleware\EnsureUserCanAccessAdmin::class,
        ]);

        $middleware->redirectGuestsTo(fn () => route('login'));

        $middleware->redirectUsersTo(function () {
            return auth()->check() && auth()->user()->canAccessAdminPanel()
                ? route('admin.dashboard')
                : route('home');
        });
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
