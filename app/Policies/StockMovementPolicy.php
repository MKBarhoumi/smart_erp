<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\StockMovement;
use App\Models\User;

class StockMovementPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, StockMovement $stockMovement): bool
    {
        return true;
    }

    public function create(User $user): bool
    {
        // Viewers cannot create stock movements
        if ($user->isViewer()) {
            return false;
        }
        return $user->hasAnyRole(['admin', 'super_admin', 'inventory_manager']);
    }

    public function update(User $user, StockMovement $stockMovement): bool
    {
        // Viewers cannot update stock movements
        if ($user->isViewer()) {
            return false;
        }
        return $user->hasAnyRole(['admin', 'super_admin', 'inventory_manager']);
    }

    public function delete(User $user, StockMovement $stockMovement): bool
    {
        // Viewers cannot delete stock movements
        if ($user->isViewer()) {
            return false;
        }
        return $user->hasAnyRole(['admin', 'super_admin']);
    }
}
