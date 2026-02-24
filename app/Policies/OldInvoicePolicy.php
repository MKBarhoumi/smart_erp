<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\OldInvoice;
use App\Models\User;

class OldInvoicePolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, OldInvoice $oldinvoice): bool
    {
        return true;
    }

    public function create(User $user): bool
    {
        return $user->hasAnyRole(['admin', 'accountant']);
    }

    public function update(User $user, OldInvoice $oldinvoice): bool
    {
        return $user->hasAnyRole(['admin', 'accountant']) && $oldinvoice->isEditable();
    }

    public function delete(User $user, OldInvoice $oldinvoice): bool
    {
        return $user->hasRole('admin') && $oldinvoice->isEditable();
    }

    public function validate(User $user, OldInvoice $oldinvoice): bool
    {
        return $user->hasAnyRole(['admin', 'accountant']);
    }

    public function sign(User $user, OldInvoice $oldinvoice): bool
    {
        return $user->hasRole('admin');
    }

    public function submit(User $user, OldInvoice $oldinvoice): bool
    {
        return $user->hasRole('admin');
    }
}
