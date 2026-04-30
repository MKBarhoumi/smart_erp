<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Models\User;
use App\Models\Customer;
use App\Models\Product;
use App\Models\Invoice;

class EnforcePlanLimits
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next, string $feature): Response
    {
        $tenant = tenancy()->tenant;

        if (!$tenant || !$tenant->plan) {
            return $next($request);
        }

        $plan = $tenant->plan;

        // Check if plan has limit for this feature
        if (!$plan->hasLimit($feature)) {
            return $next($request);
        }

        $limit = $plan->getLimit($feature);
        $currentCount = $this->getCurrentCount($feature, $tenant);

        if ($currentCount >= $limit) {
            return response()->json([
                'message' => "You have reached your plan limit for {$feature}. Please upgrade your plan.",
                'limit' => $limit,
                'current' => $currentCount,
            ], 403);
        }

        return $next($request);
    }

    /**
     * Get current count for a feature.
     */
    protected function getCurrentCount(string $feature, $tenant): int
    {
        return match($feature) {
            'users' => User::count(),
            'customers' => Customer::count(),
            'products' => Product::count(),
            'invoices' => Invoice::count(),
            default => 0,
        };
    }
}