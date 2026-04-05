<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Fortify\TwoFactorAuthenticatable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens;
    use HasFactory;
    use Notifiable;
    use TwoFactorAuthenticatable;

    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'is_active',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_active' => 'boolean',
        ];
    }

    /**
     * @return HasMany<Notification, $this>
     */
    public function appNotifications(): HasMany
    {
        return $this->hasMany(Notification::class);
    }

    /**
     * Get unread notifications count.
     */
    public function unreadNotificationsCount(): int
    {
        return $this->appNotifications()->unread()->count();
    }

    public function hasRole(string $role): bool
    {
        return $this->role === $role;
    }

    public function hasAnyRole(string|array ...$roles): bool
    {
        // Flatten if first argument is an array
        $flatRoles = [];
        foreach ($roles as $role) {
            if (is_array($role)) {
                $flatRoles = array_merge($flatRoles, $role);
            } else {
                $flatRoles[] = $role;
            }
        }
        return in_array($this->role, $flatRoles, true);
    }

    public function isViewer(): bool
    {
        return $this->role === 'viewer';
    }

    public function isAdmin(): bool
    {
        return in_array($this->role, ['admin', 'super_admin'], true);
    }

    public function isAccountant(): bool
    {
        return $this->role === 'accountant';
    }

    public function isSales(): bool
    {
        return $this->role === 'sales';
    }

    public function isInventoryManager(): bool
    {
        return $this->role === 'inventory_manager';
    }

    public function canModify(): bool
    {
        return !$this->isViewer();
    }

    /**
     * Get the user's custom role from the database.
     */
    public function getCustomRole(): ?CustomRole
    {
        return CustomRole::where('slug', $this->role)->where('is_active', true)->first();
    }

    /**
     * Check if user can access a specific page.
     */
    public function canAccessPage(string $page): bool
    {
        $role = $this->getCustomRole();
        if (!$role) return false;
        return $role->page_permissions[$page]['access'] ?? false;
    }

    /**
     * Check if user can create on a specific page.
     */
    public function canCreateOnPage(string $page): bool
    {
        $role = $this->getCustomRole();
        if (!$role) return false;
        return $role->page_permissions[$page]['create'] ?? false;
    }

    /**
     * Check if user can edit on a specific page.
     */
    public function canEditOnPage(string $page): bool
    {
        $role = $this->getCustomRole();
        if (!$role) return false;
        return $role->page_permissions[$page]['edit'] ?? false;
    }

    /**
     * Check if user can delete on a specific page.
     */
    public function canDeleteOnPage(string $page): bool
    {
        $role = $this->getCustomRole();
        if (!$role) return false;
        return $role->page_permissions[$page]['delete'] ?? false;
    }

    /**
     * Check if user has a special permission.
     */
    public function hasSpecialPermission(string $permission): bool
    {
        $role = $this->getCustomRole();
        if (!$role) return false;
        return $role->special_permissions[$permission] ?? false;
    }
}
