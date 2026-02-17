<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\View;
use Inertia\Inertia;

class HandleAppearance
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next)
    {
        $appearance = $request->cookie('appearance', 'system');

        if (! in_array($appearance, ['light', 'dark', 'system'], true)) {
            $appearance = 'system';
        }

        // Make available to Blade views
        View::share('appearance', $appearance);

        // Also share with Inertia (useful for Inertia pages)
        if (class_exists(Inertia::class)) {
            Inertia::share('appearance', $appearance);
        }

        // keep for downstream usage on the Request object
        $request->attributes->set('appearance', $appearance);

        return $next($request);
    }
}
