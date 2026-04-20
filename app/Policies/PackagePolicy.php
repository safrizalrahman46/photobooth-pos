<?php

namespace App\Policies;

use App\Models\Package;
use App\Models\User;

class PackagePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('catalog.manage') || $user->can('booking.view');
    }

    public function view(User $user, Package $package): bool
    {
        return $this->viewAny($user);
    }

    public function create(User $user): bool
    {
        return $user->can('catalog.manage');
    }

    public function update(User $user, Package $package): bool
    {
        return $user->can('catalog.manage');
    }

    public function delete(User $user, Package $package): bool
    {
        return $user->can('catalog.manage');
    }

    public function deleteAny(User $user): bool
    {
        return $user->can('catalog.manage');
    }

    public function restore(User $user, Package $package): bool
    {
        return $user->can('catalog.manage');
    }

    public function restoreAny(User $user): bool
    {
        return $user->can('catalog.manage');
    }

    public function forceDelete(User $user, Package $package): bool
    {
        return $user->can('catalog.manage');
    }

    public function forceDeleteAny(User $user): bool
    {
        return $user->can('catalog.manage');
    }
}
