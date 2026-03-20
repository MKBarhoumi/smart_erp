<?php

namespace App\Http\Controllers;

use App\Models\CustomRole;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Inertia\Inertia;

class ProfileController extends Controller
{
    /**
     * Display the profiles/permissions matrix page.
     * Only accessible by super_admin and admin roles.
     */
    public function index(Request $request)
    {
        $user = $request->user();
        
        // Only super_admin and admin can access this page
        if (!in_array($user->role, ['super_admin', 'admin'])) {
            abort(403, 'Unauthorized access to profile management.');
        }

        // Define the preferred order for system roles
        $roleOrder = ['super_admin', 'admin', 'accountant', 'sales', 'inventory_manager', 'viewer'];

        $roles = CustomRole::active()
            ->get()
            ->map(function ($role) {
                // Count users with this role
                $usersCount = \App\Models\User::where('role', $role->slug)->count();
                
                return [
                    'id' => $role->id,
                    'slug' => $role->slug,
                    'name' => $role->name,
                    'description' => $role->description,
                    'color' => $role->color,
                    'is_system' => $role->is_system,
                    'page_permissions' => $role->page_permissions,
                    'special_permissions' => $role->special_permissions,
                    'users_count' => $usersCount,
                ];
            })
            ->sortBy(function ($role) use ($roleOrder) {
                $index = array_search($role['slug'], $roleOrder);
                return $index !== false ? $index : count($roleOrder) + 1;
            })
            ->values();

        return Inertia::render('Admin/Profiles/Index', [
            'roles' => $roles,
            'availablePages' => CustomRole::availablePages(),
            'availableSpecialPermissions' => CustomRole::availableSpecialPermissions(),
            'availableColors' => CustomRole::availableColors(),
        ]);
    }

    /**
     * Store a new custom role.
     */
    public function store(Request $request)
    {
        $user = $request->user();
        
        if (!in_array($user->role, ['super_admin', 'admin'])) {
            abort(403, 'Unauthorized.');
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'slug' => [
                'required',
                'string',
                'max:50',
                'regex:/^[a-z_]+$/',
                Rule::unique('custom_roles', 'slug'),
            ],
            'description' => 'nullable|string|max:500',
            'color' => 'required|string|in:' . implode(',', array_keys(CustomRole::availableColors())),
            'page_permissions' => 'required|array',
            'special_permissions' => 'required|array',
        ], [
            'slug.regex' => 'The slug must only contain lowercase letters and underscores.',
        ]);

        CustomRole::create([
            'slug' => $validated['slug'],
            'name' => $validated['name'],
            'description' => $validated['description'] ?? null,
            'color' => $validated['color'],
            'is_system' => false,
            'is_active' => true,
            'page_permissions' => $validated['page_permissions'],
            'special_permissions' => $validated['special_permissions'],
        ]);

        return redirect()->route('admin.profiles.index')
            ->with('success', 'Role created successfully.');
    }

    /**
     * Update an existing role's permissions.
     */
    public function update(Request $request, CustomRole $role)
    {
        $user = $request->user();
        
        if (!in_array($user->role, ['super_admin', 'admin'])) {
            abort(403, 'Unauthorized.');
        }

        $rules = [
            'page_permissions' => 'required|array',
            'special_permissions' => 'required|array',
        ];

        // Non-system roles can have their name, description, and color edited
        if (!$role->is_system) {
            $rules['name'] = 'required|string|max:255';
            $rules['description'] = 'nullable|string|max:500';
            $rules['color'] = 'required|string|in:' . implode(',', array_keys(CustomRole::availableColors()));
        }

        $validated = $request->validate($rules);

        $updateData = [
            'page_permissions' => $validated['page_permissions'],
            'special_permissions' => $validated['special_permissions'],
        ];

        if (!$role->is_system) {
            $updateData['name'] = $validated['name'];
            $updateData['description'] = $validated['description'] ?? null;
            $updateData['color'] = $validated['color'];
        }

        $role->update($updateData);

        return redirect()->route('admin.profiles.index')
            ->with('success', 'Role permissions updated successfully.');
    }

    /**
     * Delete a custom role (non-system roles only).
     */
    public function destroy(Request $request, CustomRole $role)
    {
        $user = $request->user();
        
        if (!in_array($user->role, ['super_admin', 'admin'])) {
            abort(403, 'Unauthorized.');
        }

        if ($role->is_system) {
            return redirect()->route('admin.profiles.index')
                ->with('error', 'System roles cannot be deleted.');
        }

        // Check if any users have this role
        $usersCount = \App\Models\User::where('role', $role->slug)->count();
        if ($usersCount > 0) {
            return redirect()->route('admin.profiles.index')
                ->with('error', "Cannot delete role. {$usersCount} user(s) are assigned to this role.");
        }

        $role->delete();

        return redirect()->route('admin.profiles.index')
            ->with('success', 'Role deleted successfully.');
    }
}
