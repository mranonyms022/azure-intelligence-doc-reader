<?php
// bootstrap/app.php
// Laravel 11 — replace your existing bootstrap/app.php with this

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {

        // ── Role-based access middleware ──────────────────────
        $middleware->alias([
            'role' => \App\Http\Middleware\RoleMiddleware::class,
        ]);

        // ── Livewire needs session middleware on web routes ───
        $middleware->web(append: [
            \Illuminate\Http\Middleware\HandleCors::class,
        ]);

    })
    ->withExceptions(function (Exceptions $exceptions) {

        // ── Handle 403 — unauthorized role ───────────────────
        $exceptions->render(function (\Symfony\Component\HttpKernel\Exception\HttpException $e, Request $request) {
            if ($e->getStatusCode() === 403) {
                return response()->view('errors.403', [], 403);
            }
        });

    })->create();
