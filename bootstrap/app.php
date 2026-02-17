<?php

use App\Http\Middleware\EnsureUserHasRole;
use App\Http\Middleware\HandleAppearance;
use App\Http\Middleware\HandleInertiaRequests;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Middleware\AddLinkHeadersForPreloadedAssets;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Symfony\Component\HttpFoundation\Response;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->web(append: [
            \App\Http\Middleware\HandleInertiaRequests::class,
            \Illuminate\Http\Middleware\AddLinkHeadersForPreloadedAssets::class,
        ]);

        $middleware->encryptCookies(except: ['appearance', 'sidebar_state']);

        $middleware->web(append: [
            HandleAppearance::class,
            HandleInertiaRequests::class,
            AddLinkHeadersForPreloadedAssets::class,
        ]);

        // Register middleware aliases
        $middleware->alias([
            'role' => EnsureUserHasRole::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->respond(function (Response $response, Throwable $exception, Request $request) {
            $statusCode = $response->getStatusCode();

            // Map status codes to Inertia error pages
            $errorPages = [
                403 => 'Errors/Error403',
                404 => 'Errors/Error404',
                419 => 'Errors/Error419',
                500 => 'Errors/Error500',
                503 => 'Errors/Error503',
            ];

            // Only render custom error pages for specific status codes and non-local environments
            // In local environment, show the detailed error page for debugging
            if (isset($errorPages[$statusCode]) && !app()->hasDebugModeEnabled()) {
                return Inertia::render($errorPages[$statusCode], [
                    'status' => $statusCode,
                ])
                    ->toResponse($request)
                    ->setStatusCode($statusCode);
            }

            // For 404 errors, always show custom page (even in debug mode)
            if ($statusCode === 404 && !$request->expectsJson()) {
                return Inertia::render('Errors/Error404', [
                    'status' => 404,
                ])
                    ->toResponse($request)
                    ->setStatusCode(404);
            }

            return $response;
        });
    })->create();
