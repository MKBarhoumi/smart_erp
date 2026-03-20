<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Service;
use App\Models\User;

class ServicePolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Service $service): bool
    {
        return true;
    }

    public function create(User $user): bool
    {
        // Viewers and sales cannot create services
        if ($user->isViewer() || $user->isSales()) {
            return false;
        }
        return $user->hasAnyRole(['admin', 'super_admin', 'accountant']);
    }

    public function update(User $user, Service $service): bool
    {
        // Viewers and sales cannot update services
        if ($user->isViewer() || $user->isSales()) {
            return false;
        }
        return $user->hasAnyRole(['admin', 'super_admin', 'accountant']);
    }

    public function delete(User $user, Service $service): bool
    {
        // Only admins can delete services
        if ($user->isViewer()) {
            return false;
        }
        return $user->isAdmin();
    }
}
