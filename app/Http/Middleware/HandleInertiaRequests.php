<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Models\CompanySetting;
use App\Models\Notification;
use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    protected $rootView = 'app';

    /**
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        return array_merge(parent::share($request), [
            'auth' => [
                'user' => $request->user() ? [
                    'id' => $request->user()->id,
                    'name' => $request->user()->name,
                    'email' => $request->user()->email,
                    'role' => $request->user()->role,
                    'is_active' => $request->user()->is_active,
                    'email_verified_at' => $request->user()->email_verified_at,
                    'created_at' => $request->user()->created_at,
                    'is_viewer' => $request->user()->isViewer(),
                    'can_modify' => $request->user()->canModify(),
                    'is_admin' => $request->user()->isAdmin(),
                    'unread_notifications_count' => Notification::where('user_id', $request->user()->id)->whereNull('read_at')->count(),
                ] : null,
            ],
            'flash' => [
                'success' => fn () => $request->session()->get('success'),
                'error' => fn () => $request->session()->get('error'),
                'warning' => fn () => $request->session()->get('warning'),
            ],
            'company' => fn () => $request->user()
                ? CompanySetting::first()?->only([
                    'company_name',
                    'matricule_fiscal',
                    'logo_path',
                    'default_timbre_fiscal',
                ])
                : null,
        ]);
    }
}
