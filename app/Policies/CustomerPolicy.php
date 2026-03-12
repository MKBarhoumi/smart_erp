<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Customer;
use App\Models\User;

class CustomerPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Customer $customer): bool
    {
        return true;
    }

    public function create(User $user): bool
    {
        // Viewers cannot create
        if ($user->isViewer()) {
            return false;
        }
        return $user->hasAnyRole(['admin', 'accountant', 'sales']);
    }

    public function update(User $user, Customer $customer): bool
    {
        // Viewers cannot update
        if ($user->isViewer()) {
            return false;
        }
        return $user->hasAnyRole(['admin', 'accountant']);
    }

    public function delete(User $user, Customer $customer): bool
    {
        // Viewers cannot delete
        if ($user->isViewer()) {
            return false;
        }
        return $user->hasRole('admin');
    }
}
