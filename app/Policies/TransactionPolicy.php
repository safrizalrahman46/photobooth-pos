<?php

namespace App\Policies;

use App\Models\Transaction;
use App\Models\User;

class TransactionPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('transaction.view');
    }

    public function view(User $user, Transaction $transaction): bool
    {
        return $user->can('transaction.view');
    }

    public function create(User $user): bool
    {
        return $user->can('transaction.manage');
    }

    public function update(User $user, Transaction $transaction): bool
    {
        return $user->can('transaction.manage');
    }

    public function delete(User $user, Transaction $transaction): bool
    {
        return $user->can('transaction.manage');
    }

    public function deleteAny(User $user): bool
    {
        return $user->can('transaction.manage');
    }

    public function restore(User $user, Transaction $transaction): bool
    {
        return $user->can('transaction.manage');
    }

    public function restoreAny(User $user): bool
    {
        return $user->can('transaction.manage');
    }

    public function forceDelete(User $user, Transaction $transaction): bool
    {
        return $user->can('transaction.manage');
    }

    public function forceDeleteAny(User $user): bool
    {
        return $user->can('transaction.manage');
    }
}
