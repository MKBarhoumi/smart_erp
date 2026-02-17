<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Laravel\Fortify\Features;
use Symfony\Component\HttpFoundation\Response as HttpResponse;

class TwoFactorAuthenticationController extends Controller
{
    /**
     * Display the two-factor authentication settings page.
     */
    public function show(Request $request): Response|HttpResponse
    {
        if (! Features::canManageTwoFactorAuthentication()) {
            abort(403);
        }

        $user = $request->user();

        return Inertia::render('settings/two-factor', [
            'twoFactorEnabled' => $user->hasEnabledTwoFactorAuthentication(),
            'twoFactorConfirmed' => $user->two_factor_confirmed_at !== null,
            'recoveryCodes' => $user->recoveryCodes(),
        ]);
    }
}
