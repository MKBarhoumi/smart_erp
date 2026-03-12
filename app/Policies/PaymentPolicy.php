<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Payment;
use App\Models\User;

class PaymentPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Payment $payment): bool
    {
        return true;
    }

    public function create(User $user): bool
    {
        // Viewers cannot create payments
        if ($user->isViewer()) {
            return false;
        }
        return $user->hasAnyRole(['admin', 'super_admin', 'accountant', 'sales']);
    }

    public function update(User $user, Payment $payment): bool
    {
        // Viewers cannot update payments
        if ($user->isViewer()) {
            return false;
        }
        return $user->hasAnyRole(['admin', 'super_admin', 'accountant']);
    }

    public function delete(User $user, Payment $payment): bool
    {
        // Viewers cannot delete payments
        if ($user->isViewer()) {
            return false;
        }
        return $user->hasAnyRole(['admin', 'super_admin']);
    }
}
