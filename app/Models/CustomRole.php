<?php

namespace App\Models;

use App\Concerns\LogsActivity;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class CustomRole extends Model
{
    use HasFactory, HasUuids, SoftDeletes, LogsActivity;

    protected $fillable = [
        'slug',
        'name',
        'description',
        'color',
        'is_system',
        'is_active',
        'page_permissions',
        'special_permissions',
    ];

    protected $casts = [
        'is_system' => 'boolean',
        'is_active' => 'boolean',
        'page_permissions' => 'array',
        'special_permissions' => 'array',
    ];

    /**
     * Get roles that can be assigned to users.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Get system roles that cannot be deleted.
     */
    public function scopeSystem($query)
    {
        return $query->where('is_system', true);
    }

    /**
     * Get custom (non-system) roles.
     */
    public function scopeCustom($query)
    {
        return $query->where('is_system', false);
    }

    /**
     * Available color options for roles.
     */
    public static function availableColors(): array
    {
        return [
            'purple' => 'Purple',
            'blue' => 'Blue',
            'emerald' => 'Emerald',
            'amber' => 'Amber',
            'indigo' => 'Indigo',
            'gray' => 'Gray',
            'red' => 'Red',
            'pink' => 'Pink',
            'cyan' => 'Cyan',
            'teal' => 'Teal',
        ];
    }

    /**
     * Available pages for permissions.
     */
    public static function availablePages(): array
    {
        return [
            'dashboard' => 'Dashboard',
            'invoices' => 'TEIF Invoices',
            'oldinvoices' => 'Old Invoices',
            'customers' => 'Customers',
            'products' => 'Products',
            'services' => 'Services',
            'payments' => 'Payments',
            'inventory' => 'Inventory',
            'reports' => 'Reports',
            'settings' => 'Settings',
            'admin_users' => 'User Management',
            'admin_audit' => 'Audit Log',
            'admin_profiles' => 'Profile Manager',
        ];
    }

    /**
     * Available special permissions.
     */
    public static function availableSpecialPermissions(): array
    {
        return [
            'canValidateInvoices' => 'Validate Invoices',
            'canImportXML' => 'Import XML',
            'canExportData' => 'Export Data',
            'canViewFinancialKPIs' => 'View Financial KPIs',
            'canManageOwnProfile' => 'Manage Own Profile',
        ];
    }

    /**
     * Get default page permissions structure.
     */
    public static function defaultPagePermissions(): array
    {
        $pages = self::availablePages();
        $permissions = [];
        
        foreach ($pages as $key => $name) {
            $permissions[$key] = [
                'access' => false,
                'view' => false,
                'create' => false,
                'edit' => false,
                'delete' => false,
            ];
        }
        
        return $permissions;
    }

    /**
     * Get default special permissions structure.
     */
    public static function defaultSpecialPermissions(): array
    {
        return [
            'canValidateInvoices' => false,
            'canImportXML' => false,
            'canExportData' => true,
            'canViewFinancialKPIs' => false,
            'canManageOwnProfile' => true,
        ];
    }

    /**
     * Users with this role.
     */
    public function users()
    {
        return User::where('role', $this->slug)->get();
    }

    /**
     * Count users with this role.
     */
    public function getUsersCountAttribute(): int
    {
        return User::where('role', $this->slug)->count();
    }
}
