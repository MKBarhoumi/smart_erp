<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Product;
use App\Models\User;

class ProductPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Product $product): bool
    {
        return true;
    }

    public function create(User $user): bool
    {
        // Viewers cannot create
        if ($user->isViewer()) {
            return false;
        }
        return $user->hasAnyRole(['admin', 'accountant', 'inventory_manager']);
    }

    public function update(User $user, Product $product): bool
    {
        // Viewers cannot update
        if ($user->isViewer()) {
            return false;
        }
        return $user->hasAnyRole(['admin', 'accountant', 'inventory_manager']);
    }

    public function delete(User $user, Product $product): bool
    {
        // Viewers cannot delete
        if ($user->isViewer()) {
            return false;
        }
        return $user->hasRole('admin');
    }
}
