<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Plan;
use App\Models\Tenant;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

class BillingController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * Show billing page.
     */
    public function index()
    {
        ensureCentral();

        $tenant = Auth::user()->tenant;
        $plans = Plan::active()->ordered()->get();

        return inertia('Billing/Index', [
            'tenant' => $tenant,
            'plans' => $plans,
            'subscription' => $tenant->subscription('default'),
            'paymentMethod' => $tenant->defaultPaymentMethod(),
            'invoices' => $tenant->invoices(),
        ]);
    }

    /**
     * Create checkout session for new subscription.
     */
    public function checkout(Request $request): JsonResponse
    {
        ensureCentral();

        $request->validate([
            'plan_id' => 'required|exists:plans,id',
        ]);

        $tenant = Auth::user()->tenant;
        $plan = Plan::findOrFail($request->plan_id);

        if ($plan->isFree()) {
            $tenant->plan_id = $plan->id;
            $tenant->save();

            return response()->json([
                'message' => 'Free plan activated successfully',
                'redirect' => route('billing.index'),
            ]);
        }

        if ($tenant->subscribed('default')) {
            return response()->json([
                'message' => 'You already have an active subscription',
            ], 400);
        }

        // 🔥 CRITICAL: Ensure we're in central context for Stripe operations
        tenancy()->end();

        try {
            $checkoutSession = $tenant->newSubscription('default', $plan->stripe_price_id)
                ->checkout([
                    'success_url' => route('billing.success') . '?session_id={CHECKOUT_SESSION_ID}',
                    'cancel_url' => route('billing.cancel'),
                    'metadata' => [
                        'plan_id' => $plan->id,
                        'tenant_id' => $tenant->id,
                    ],
                ]);

            return response()->json([
                'url' => $checkoutSession->url,
            ]);
        } finally {
            // Restore tenant context if needed
            if ($tenant->domains()->exists()) {
                tenancy()->initialize($tenant);
            }
        }
    }

    /**
     * Handle successful checkout.
     */
    public function success(Request $request)
    {
        ensureCentral();

        $tenant = Auth::user()->tenant;

        if ($request->has('session_id')) {
            $session = $tenant->stripe()->checkout->sessions->retrieve($request->session_id);

            if ($session->payment_status === 'paid') {
                $planId = $session->metadata->plan_id ?? null;
                if ($planId) {
                    $tenant->plan_id = $planId;
                    $tenant->save();
                }
            }
        }

        return redirect()->route('billing.index')->with('success', 'Subscription activated successfully!');
    }

    /**
     * Handle cancelled checkout.
     */
    public function cancel()
    {
        ensureCentral();

        return redirect()->route('billing.index')->with('info', 'Subscription cancelled.');
    }

    /**
     * Create billing portal session.
     */
    public function portal(): JsonResponse
    {
        ensureCentral();

        $tenant = Auth::user()->tenant;

        if (!$tenant->subscribed('default')) {
            return response()->json([
                'message' => 'No active subscription found',
            ], 400);
        }

        $portalSession = $tenant->subscription('default')->createPortalSession([
            'return_url' => route('billing.index'),
        ]);

        return response()->json([
            'url' => $portalSession->url,
        ]);
    }

    /**
     * Cancel subscription.
     */
    public function cancelSubscription(): JsonResponse
    {
        ensureCentral();

        $tenant = Auth::user()->tenant;

        if (!$tenant->subscribed('default')) {
            return response()->json([
                'message' => 'No active subscription to cancel',
            ], 400);
        }

        $tenant->subscription('default')->cancel();

        return response()->json([
            'message' => 'Subscription cancelled successfully',
        ]);
    }

    /**
     * Resume cancelled subscription.
     */
    public function resumeSubscription(): JsonResponse
    {
        ensureCentral();

        $tenant = Auth::user()->tenant;

        if (!$tenant->subscription('default')) {
            return response()->json([
                'message' => 'No subscription found',
            ], 400);
        }

        if (!$tenant->subscription('default')->onGracePeriod()) {
            return response()->json([
                'message' => 'Subscription cannot be resumed',
            ], 400);
        }

        $tenant->subscription('default')->resume();

        return response()->json([
            'message' => 'Subscription resumed successfully',
        ]);
    }

    /**
     * Swap subscription to another plan.
     */
    public function swapPlan(Request $request): JsonResponse
    {
        ensureCentral();

        $request->validate([
            'plan_id' => 'required|exists:plans,id',
        ]);

        $tenant = Auth::user()->tenant;
        $newPlan = Plan::findOrFail($request->plan_id);

        if (!$tenant->subscribed('default')) {
            return response()->json([
                'message' => 'No active subscription found',
            ], 400);
        }

        if ($newPlan->isFree()) {
            $tenant->subscription('default')->cancel();
            $tenant->plan_id = $newPlan->id;
            $tenant->save();

            return response()->json([
                'message' => 'Switched to free plan successfully',
            ]);
        }

        // 🔥 CRITICAL: Ensure central context for Stripe operations
        tenancy()->end();

        try {
            // Use swapAndInvoice for proper proration handling
            $tenant->subscription('default')->swapAndInvoice($newPlan->stripe_price_id);
            $tenant->plan_id = $newPlan->id;
            $tenant->save();

            return response()->json([
                'message' => 'Plan changed successfully',
            ]);
        } finally {
            // Restore tenant context if needed
            if ($tenant->domains()->exists()) {
                tenancy()->initialize($tenant);
            }
        }
    }

    /**
     * Get subscription details.
     */
    public function subscription(): JsonResponse
    {
        ensureCentral();

        $tenant = Auth::user()->tenant;

        return response()->json([
            'subscribed' => $tenant->subscribed('default'),
            'on_trial' => $tenant->onTrial(),
            'on_grace_period' => $tenant->subscription('default')?->onGracePeriod() ?? false,
            'trial_ends_at' => $tenant->trial_ends_at,
            'ends_at' => $tenant->subscription('default')?->ends_at,
            'plan' => $tenant->plan,
        ]);
    }

    /**
     * Get invoices.
     */
    public function invoices(): JsonResponse
    {
        ensureCentral();

        $tenant = Auth::user()->tenant;

        return response()->json([
            'invoices' => $tenant->invoices()->map(function ($invoice) {
                return [
                    'id' => $invoice->id,
                    'date' => $invoice->date()->toFormattedDateString(),
                    'total' => $invoice->total(),
                    'status' => $invoice->status,
                    'url' => $invoice->hosted_invoice_url,
                ];
            }),
        ]);
    }

    /**
     * Download invoice.
     */
    public function downloadInvoice($invoiceId)
    {
        ensureCentral();

        $tenant = Auth::user()->tenant;

        return $tenant->downloadInvoice($invoiceId);
    }

    /**
     * Add payment method.
     */
    public function addPaymentMethod(Request $request): JsonResponse
    {
        ensureCentral();

        $request->validate([
            'payment_method_id' => 'required|string',
        ]);

        $tenant = Auth::user()->tenant;

        // 🔥 CRITICAL: Ensure central context for Stripe operations
        tenancy()->end();

        try {
            $tenant->addPaymentMethod($request->payment_method_id);

            return response()->json([
                'message' => 'Payment method added successfully',
            ]);
        } finally {
            // Restore tenant context if needed
            if ($tenant->domains()->exists()) {
                tenancy()->initialize($tenant);
            }
        }
    }

    /**
     * Update default payment method.
     */
    public function updateDefaultPaymentMethod(Request $request): JsonResponse
    {
        ensureCentral();

        $request->validate([
            'payment_method_id' => 'required|string',
        ]);

        $tenant = Auth::user()->tenant;

        // 🔥 CRITICAL: Ensure central context for Stripe operations
        tenancy()->end();

        try {
            $tenant->updateDefaultPaymentMethod($request->payment_method_id);

            return response()->json([
                'message' => 'Default payment method updated successfully',
            ]);
        } finally {
            // Restore tenant context if needed
            if ($tenant->domains()->exists()) {
                tenancy()->initialize($tenant);
            }
        }
    }

    /**
     * Get payment methods.
     */
    public function paymentMethods(): JsonResponse
    {
        ensureCentral();

        $tenant = Auth::user()->tenant;

        // 🔥 CRITICAL: Ensure central context for Stripe operations
        tenancy()->end();

        try {
            $paymentMethods = $tenant->paymentMethods();

            return response()->json([
                'payment_methods' => $paymentMethods,
                'default_payment_method' => $tenant->defaultPaymentMethod(),
            ]);
        } finally {
            // Restore tenant context if needed
            if ($tenant->domains()->exists()) {
                tenancy()->initialize($tenant);
            }
        }
    }

    /**
     * Delete payment method.
     */
    public function deletePaymentMethod(Request $request): JsonResponse
    {
        ensureCentral();

        $request->validate([
            'payment_method_id' => 'required|string',
        ]);

        $tenant = Auth::user()->tenant;

        // 🔥 CRITICAL: Ensure central context for Stripe operations
        tenancy()->end();

        try {
            $paymentMethod = $tenant->findPaymentMethod($request->payment_method_id);

            if (!$paymentMethod) {
                return response()->json([
                    'message' => 'Payment method not found',
                ], 404);
            }

            $paymentMethod->delete();

            return response()->json([
                'message' => 'Payment method deleted successfully',
            ]);
        } finally {
            // Restore tenant context if needed
            if ($tenant->domains()->exists()) {
                tenancy()->initialize($tenant);
            }
        }
    }
}