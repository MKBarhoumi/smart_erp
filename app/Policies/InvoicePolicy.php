<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Invoice;
use App\Models\User;

class InvoicePolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Invoice $invoice): bool
    {
        return true;
    }

    public function create(User $user): bool
    {
        return $user->hasAnyRole(['admin', 'accountant']);
    }

    public function update(User $user, Invoice $invoice): bool
    {
        return $user->hasAnyRole(['admin', 'accountant']) && $invoice->isEditable();
    }

    public function delete(User $user, Invoice $invoice): bool
    {
        return $user->hasRole('admin') && $invoice->isEditable();
    }

    public function validate(User $user, Invoice $invoice): bool
    {
        return $user->hasAnyRole(['admin', 'accountant']);
    }

    public function sign(User $user, Invoice $invoice): bool
    {
        return $user->hasRole('admin');
    }

    public function submit(User $user, Invoice $invoice): bool
    {
        return $user->hasRole('admin');
    }
}
