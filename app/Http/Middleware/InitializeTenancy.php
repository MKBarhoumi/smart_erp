<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Stancl\Tenancy\Tenancy;
use Stancl\Tenancy\Resolvers\DomainTenantResolver;

/**
 * Initialize Tenancy Middleware
 *
 * This middleware identifies the tenant from the domain and initializes tenancy.
 *
 * @package App\Http\Middleware
 */
class InitializeTenancy
{
    /**
     * Handle an incoming request.
     *
     * @param Request $request
     * @param Closure $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        // Get the host from the request
        $host = $request->getHost();

        // Check if this is a central domain
        $centralDomains = config('tenancy.central_domains', []);
        if (in_array($host, $centralDomains)) {
            // This is a central domain, don't initialize tenancy
            return $next($request);
        }

        // Try to resolve tenant from domain
        $resolver = app(DomainTenantResolver::class);
        $domain = $resolver->findByDomain($host);

        if (!$domain) {
            // No tenant found for this domain
            // You can either return 404 or redirect to central domain
            // For now, we'll continue without tenancy
            return $next($request);
        }

        // Initialize tenancy for this tenant
        tenancy()->initialize($domain->tenant);

        return $next($request);
    }
}
