<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureTenantIsSubscribed
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $tenant = tenancy()->tenant;

        if (!$tenant) {
            return redirect()->route('home')->with('error', 'Tenant not found');
        }

        // Check if tenant is on trial
        if ($tenant->onTrial()) {
            return $next($request);
        }

        // Check if tenant has active subscription
        if (!$tenant->subscribed('default')) {
            return redirect()->route('billing.index')->with('warning', 'Please subscribe to access this feature');
        }

        // Check if subscription is on grace period
        if ($tenant->subscription('default')->onGracePeriod()) {
            return $next($request);
        }

        // Check if subscription is active
        if (!$tenant->subscription('default')->active()) {
            return redirect()->route('billing.index')->with('error', 'Your subscription has expired. Please renew to continue.');
        }

        return $next($request);
    }
}