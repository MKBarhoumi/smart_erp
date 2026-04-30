<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\CustomRole;
use Illuminate\Database\Seeder;

class RolesAndPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        $roles = [
            [
                'name' => 'admin',
                'display_name' => 'Administrator',
                'description' => 'Full access to all features',
                'permissions' => [
                    'manage-users',
                    'manage-settings',
                    'manage-customers',
                    'manage-products',
                    'manage-services',
                    'manage-invoices',
                    'manage-payments',
                    'manage-reports',
                    'manage-inventory',
                ],
            ],
            [
                'name' => 'manager',
                'display_name' => 'Manager',
                'description' => 'Access to most features except user management',
                'permissions' => [
                    'manage-customers',
                    'manage-products',
                    'manage-services',
                    'manage-invoices',
                    'manage-payments',
                    'manage-reports',
                    'manage-inventory',
                ],
            ],
            [
                'name' => 'accountant',
                'display_name' => 'Accountant',
                'description' => 'Access to financial features',
                'permissions' => [
                    'manage-invoices',
                    'manage-payments',
                    'manage-reports',
                ],
            ],
            [
                'name' => 'sales',
                'display_name' => 'Sales Representative',
                'description' => 'Access to sales features',
                'permissions' => [
                    'manage-customers',
                    'manage-products',
                    'manage-services',
                    'manage-invoices',
                ],
            ],
        ];

        foreach ($roles as $role) {
            CustomRole::create([
                'name' => $role['name'],
                'display_name' => $role['display_name'],
                'description' => $role['description'],
                'permissions' => $role['permissions'],
            ]);
        }
    }
}