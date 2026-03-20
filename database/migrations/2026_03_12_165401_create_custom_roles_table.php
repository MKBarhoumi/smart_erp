<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('custom_roles', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('slug')->unique(); // e.g., 'sales_manager', 'warehouse_staff'
            $table->string('name'); // Display name
            $table->text('description')->nullable();
            $table->string('color')->default('gray'); // For UI display
            $table->boolean('is_system')->default(false); // System roles cannot be deleted
            $table->boolean('is_active')->default(true);
            $table->json('page_permissions'); // JSON object with page -> permission mapping
            $table->json('special_permissions'); // JSON object with special permissions
            $table->timestamps();
            $table->softDeletes();
        });

        // Seed the default system roles
        $this->seedSystemRoles();
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('custom_roles');
    }

    /**
     * Seed the default system roles.
     */
    private function seedSystemRoles(): void
    {
        $roles = [
            [
                'slug' => 'super_admin',
                'name' => 'Super Admin',
                'description' => 'Full system access with all permissions',
                'color' => 'purple',
                'is_system' => true,
                'page_permissions' => json_encode([
                    'dashboard' => ['access' => true, 'view' => true, 'create' => true, 'edit' => true, 'delete' => true],
                    'invoices' => ['access' => true, 'view' => true, 'create' => true, 'edit' => true, 'delete' => true],
                    'oldinvoices' => ['access' => true, 'view' => true, 'create' => true, 'edit' => true, 'delete' => true],
                    'customers' => ['access' => true, 'view' => true, 'create' => true, 'edit' => true, 'delete' => true],
                    'products' => ['access' => true, 'view' => true, 'create' => true, 'edit' => true, 'delete' => true],
                    'services' => ['access' => true, 'view' => true, 'create' => true, 'edit' => true, 'delete' => true],
                    'payments' => ['access' => true, 'view' => true, 'create' => true, 'edit' => true, 'delete' => true],
                    'inventory' => ['access' => true, 'view' => true, 'create' => true, 'edit' => true, 'delete' => true],
                    'reports' => ['access' => true, 'view' => true, 'create' => false, 'edit' => false, 'delete' => false],
                    'settings' => ['access' => true, 'view' => true, 'create' => true, 'edit' => true, 'delete' => true],
                    'admin_users' => ['access' => true, 'view' => true, 'create' => true, 'edit' => true, 'delete' => true],
                    'admin_audit' => ['access' => true, 'view' => true, 'create' => false, 'edit' => false, 'delete' => false],
                    'admin_profiles' => ['access' => true, 'view' => true, 'create' => true, 'edit' => true, 'delete' => true],
                ]),
                'special_permissions' => json_encode([
                    'canValidateInvoices' => true,
                    'canImportXML' => true,
                    'canExportData' => true,
                    'canViewFinancialKPIs' => true,
                    'canManageOwnProfile' => true,
                ]),
            ],
            [
                'slug' => 'admin',
                'name' => 'Admin',
                'description' => 'Administrative access with most permissions',
                'color' => 'blue',
                'is_system' => true,
                'page_permissions' => json_encode([
                    'dashboard' => ['access' => true, 'view' => true, 'create' => true, 'edit' => true, 'delete' => true],
                    'invoices' => ['access' => true, 'view' => true, 'create' => true, 'edit' => true, 'delete' => true],
                    'oldinvoices' => ['access' => true, 'view' => true, 'create' => true, 'edit' => true, 'delete' => true],
                    'customers' => ['access' => true, 'view' => true, 'create' => true, 'edit' => true, 'delete' => true],
                    'products' => ['access' => true, 'view' => true, 'create' => true, 'edit' => true, 'delete' => true],
                    'services' => ['access' => true, 'view' => true, 'create' => true, 'edit' => true, 'delete' => true],
                    'payments' => ['access' => true, 'view' => true, 'create' => true, 'edit' => true, 'delete' => true],
                    'inventory' => ['access' => true, 'view' => true, 'create' => true, 'edit' => true, 'delete' => true],
                    'reports' => ['access' => true, 'view' => true, 'create' => false, 'edit' => false, 'delete' => false],
                    'settings' => ['access' => true, 'view' => true, 'create' => true, 'edit' => true, 'delete' => true],
                    'admin_users' => ['access' => true, 'view' => true, 'create' => true, 'edit' => true, 'delete' => true],
                    'admin_audit' => ['access' => true, 'view' => true, 'create' => false, 'edit' => false, 'delete' => false],
                    'admin_profiles' => ['access' => true, 'view' => true, 'create' => true, 'edit' => true, 'delete' => true],
                ]),
                'special_permissions' => json_encode([
                    'canValidateInvoices' => true,
                    'canImportXML' => true,
                    'canExportData' => true,
                    'canViewFinancialKPIs' => true,
                    'canManageOwnProfile' => true,
                ]),
            ],
            [
                'slug' => 'accountant',
                'name' => 'Accountant',
                'description' => 'Financial operations and reporting access',
                'color' => 'emerald',
                'is_system' => true,
                'page_permissions' => json_encode([
                    'dashboard' => ['access' => true, 'view' => true, 'create' => false, 'edit' => false, 'delete' => false],
                    'invoices' => ['access' => true, 'view' => true, 'create' => true, 'edit' => true, 'delete' => false],
                    'oldinvoices' => ['access' => true, 'view' => true, 'create' => true, 'edit' => true, 'delete' => false],
                    'customers' => ['access' => true, 'view' => true, 'create' => true, 'edit' => true, 'delete' => false],
                    'products' => ['access' => true, 'view' => true, 'create' => true, 'edit' => true, 'delete' => false],
                    'services' => ['access' => true, 'view' => true, 'create' => true, 'edit' => true, 'delete' => false],
                    'payments' => ['access' => true, 'view' => true, 'create' => true, 'edit' => true, 'delete' => false],
                    'inventory' => ['access' => false, 'view' => false, 'create' => false, 'edit' => false, 'delete' => false],
                    'reports' => ['access' => true, 'view' => true, 'create' => false, 'edit' => false, 'delete' => false],
                    'settings' => ['access' => false, 'view' => false, 'create' => false, 'edit' => false, 'delete' => false],
                    'admin_users' => ['access' => false, 'view' => false, 'create' => false, 'edit' => false, 'delete' => false],
                    'admin_audit' => ['access' => false, 'view' => false, 'create' => false, 'edit' => false, 'delete' => false],
                    'admin_profiles' => ['access' => false, 'view' => false, 'create' => false, 'edit' => false, 'delete' => false],
                ]),
                'special_permissions' => json_encode([
                    'canValidateInvoices' => true,
                    'canImportXML' => true,
                    'canExportData' => true,
                    'canViewFinancialKPIs' => true,
                    'canManageOwnProfile' => true,
                ]),
            ],
            [
                'slug' => 'sales',
                'name' => 'Sales',
                'description' => 'Customer and invoice management, no financial data',
                'color' => 'amber',
                'is_system' => true,
                'page_permissions' => json_encode([
                    'dashboard' => ['access' => true, 'view' => true, 'create' => false, 'edit' => false, 'delete' => false],
                    'invoices' => ['access' => true, 'view' => true, 'create' => true, 'edit' => true, 'delete' => false],
                    'oldinvoices' => ['access' => true, 'view' => true, 'create' => false, 'edit' => false, 'delete' => false],
                    'customers' => ['access' => true, 'view' => true, 'create' => true, 'edit' => true, 'delete' => false],
                    'products' => ['access' => true, 'view' => true, 'create' => false, 'edit' => false, 'delete' => false],
                    'services' => ['access' => true, 'view' => true, 'create' => false, 'edit' => false, 'delete' => false],
                    'payments' => ['access' => true, 'view' => true, 'create' => false, 'edit' => false, 'delete' => false],
                    'inventory' => ['access' => false, 'view' => false, 'create' => false, 'edit' => false, 'delete' => false],
                    'reports' => ['access' => true, 'view' => true, 'create' => false, 'edit' => false, 'delete' => false],
                    'settings' => ['access' => false, 'view' => false, 'create' => false, 'edit' => false, 'delete' => false],
                    'admin_users' => ['access' => false, 'view' => false, 'create' => false, 'edit' => false, 'delete' => false],
                    'admin_audit' => ['access' => false, 'view' => false, 'create' => false, 'edit' => false, 'delete' => false],
                    'admin_profiles' => ['access' => false, 'view' => false, 'create' => false, 'edit' => false, 'delete' => false],
                ]),
                'special_permissions' => json_encode([
                    'canValidateInvoices' => false,
                    'canImportXML' => true,
                    'canExportData' => true,
                    'canViewFinancialKPIs' => false,
                    'canManageOwnProfile' => true,
                ]),
            ],
            [
                'slug' => 'inventory_manager',
                'name' => 'Inventory Manager',
                'description' => 'Product and service management access',
                'color' => 'indigo',
                'is_system' => true,
                'page_permissions' => json_encode([
                    'dashboard' => ['access' => true, 'view' => true, 'create' => false, 'edit' => false, 'delete' => false],
                    'invoices' => ['access' => false, 'view' => false, 'create' => false, 'edit' => false, 'delete' => false],
                    'oldinvoices' => ['access' => false, 'view' => false, 'create' => false, 'edit' => false, 'delete' => false],
                    'customers' => ['access' => false, 'view' => false, 'create' => false, 'edit' => false, 'delete' => false],
                    'products' => ['access' => true, 'view' => true, 'create' => true, 'edit' => true, 'delete' => true],
                    'services' => ['access' => true, 'view' => true, 'create' => true, 'edit' => true, 'delete' => true],
                    'payments' => ['access' => false, 'view' => false, 'create' => false, 'edit' => false, 'delete' => false],
                    'inventory' => ['access' => true, 'view' => true, 'create' => true, 'edit' => true, 'delete' => true],
                    'reports' => ['access' => true, 'view' => true, 'create' => false, 'edit' => false, 'delete' => false],
                    'settings' => ['access' => false, 'view' => false, 'create' => false, 'edit' => false, 'delete' => false],
                    'admin_users' => ['access' => false, 'view' => false, 'create' => false, 'edit' => false, 'delete' => false],
                    'admin_audit' => ['access' => false, 'view' => false, 'create' => false, 'edit' => false, 'delete' => false],
                    'admin_profiles' => ['access' => false, 'view' => false, 'create' => false, 'edit' => false, 'delete' => false],
                ]),
                'special_permissions' => json_encode([
                    'canValidateInvoices' => false,
                    'canImportXML' => false,
                    'canExportData' => true,
                    'canViewFinancialKPIs' => false,
                    'canManageOwnProfile' => true,
                ]),
            ],
            [
                'slug' => 'viewer',
                'name' => 'Viewer',
                'description' => 'Read-only access to all resources',
                'color' => 'gray',
                'is_system' => true,
                'page_permissions' => json_encode([
                    'dashboard' => ['access' => true, 'view' => true, 'create' => false, 'edit' => false, 'delete' => false],
                    'invoices' => ['access' => true, 'view' => true, 'create' => false, 'edit' => false, 'delete' => false],
                    'oldinvoices' => ['access' => true, 'view' => true, 'create' => false, 'edit' => false, 'delete' => false],
                    'customers' => ['access' => true, 'view' => true, 'create' => false, 'edit' => false, 'delete' => false],
                    'products' => ['access' => true, 'view' => true, 'create' => false, 'edit' => false, 'delete' => false],
                    'services' => ['access' => true, 'view' => true, 'create' => false, 'edit' => false, 'delete' => false],
                    'payments' => ['access' => true, 'view' => true, 'create' => false, 'edit' => false, 'delete' => false],
                    'inventory' => ['access' => true, 'view' => true, 'create' => false, 'edit' => false, 'delete' => false],
                    'reports' => ['access' => true, 'view' => true, 'create' => false, 'edit' => false, 'delete' => false],
                    'settings' => ['access' => false, 'view' => false, 'create' => false, 'edit' => false, 'delete' => false],
                    'admin_users' => ['access' => false, 'view' => false, 'create' => false, 'edit' => false, 'delete' => false],
                    'admin_audit' => ['access' => false, 'view' => false, 'create' => false, 'edit' => false, 'delete' => false],
                    'admin_profiles' => ['access' => false, 'view' => false, 'create' => false, 'edit' => false, 'delete' => false],
                ]),
                'special_permissions' => json_encode([
                    'canValidateInvoices' => false,
                    'canImportXML' => false,
                    'canExportData' => true,
                    'canViewFinancialKPIs' => true,
                    'canManageOwnProfile' => true,
                ]),
            ],
        ];

        foreach ($roles as $role) {
            \DB::table('custom_roles')->insert(array_merge($role, [
                'id' => \Str::uuid()->toString(),
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]));
        }
    }
};
