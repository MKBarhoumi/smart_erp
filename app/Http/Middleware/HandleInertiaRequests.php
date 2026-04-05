<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Models\CompanySetting;
use App\Models\CustomRole;
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
        $user = $request->user();
        $rolePermissions = null;

        if ($user) {
            // Admin and super_admin users get full access to all pages
            if (in_array($user->role, ['admin', 'super_admin'], true)) {
                $rolePermissions = [
                    'page_permissions' => [
                        'dashboard' => ['access' => true, 'view' => true, 'create' => true, 'edit' => true, 'delete' => true],
                        'invoices' => ['access' => true, 'view' => true, 'create' => true, 'edit' => true, 'delete' => true],
                        'oldinvoices' => ['access' => true, 'view' => true, 'create' => true, 'edit' => true, 'delete' => true],
                        'customers' => ['access' => true, 'view' => true, 'create' => true, 'edit' => true, 'delete' => true],
                        'products' => ['access' => true, 'view' => true, 'create' => true, 'edit' => true, 'delete' => true],
                        'services' => ['access' => true, 'view' => true, 'create' => true, 'edit' => true, 'delete' => true],
                        'payments' => ['access' => true, 'view' => true, 'create' => true, 'edit' => true, 'delete' => true],
                        'inventory' => ['access' => true, 'view' => true, 'create' => true, 'edit' => true, 'delete' => true],
                        'reports' => ['access' => true, 'view' => true, 'create' => true, 'edit' => true, 'delete' => true],
                        'settings' => ['access' => true, 'view' => true, 'create' => true, 'edit' => true, 'delete' => true],
                        'admin_users' => ['access' => true, 'view' => true, 'create' => true, 'edit' => true, 'delete' => true],
                        'admin_audit' => ['access' => true, 'view' => true, 'create' => true, 'edit' => true, 'delete' => true],
                        'admin_profiles' => ['access' => true, 'view' => true, 'create' => true, 'edit' => true, 'delete' => true],
                    ],
                    'special_permissions' => [
                        'canValidateInvoices' => true,
                        'canImportXML' => true,
                        'canExportData' => true,
                        'canViewFinancialKPIs' => true,
                        'canManageOwnProfile' => true,
                    ],
                ];
            } else {
                // Get role permissions from the database for non-admin roles
                $customRole = CustomRole::where('slug', $user->role)->where('is_active', true)->first();
                if ($customRole) {
                    $rolePermissions = [
                        'page_permissions' => $customRole->page_permissions ?? [],
                        'special_permissions' => $customRole->special_permissions ?? [],
                    ];
                }
            }
        }

        return array_merge(parent::share($request), [
            'auth' => [
                'user' => $user ? [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'role' => $user->role,
                    'is_active' => $user->is_active,
                    'email_verified_at' => $user->email_verified_at,
                    'created_at' => $user->created_at,
                    'is_viewer' => $user->isViewer(),
                    'can_modify' => $user->canModify(),
                    'is_admin' => $user->isAdmin(),
                    'unread_notifications_count' => Notification::where('user_id', $user->id)->whereNull('read_at')->count(),
                    'permissions' => $rolePermissions,
                ] : null,
            ],
            'flash' => [
                'success' => fn () => $request->session()->get('success'),
                'error' => fn () => $request->session()->get('error'),
                'warning' => fn () => $request->session()->get('warning'),
            ],
            'company' => fn () => $user
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
