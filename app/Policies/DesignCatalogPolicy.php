<?php

namespace App\Policies;

use App\Models\DesignCatalog;
use App\Models\User;

class DesignCatalogPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('catalog.manage');
    }

    public function view(User $user, DesignCatalog $designCatalog): bool
    {
        return $user->can('catalog.manage');
    }

    public function create(User $user): bool
    {
        return $user->can('catalog.manage');
    }

    public function update(User $user, DesignCatalog $designCatalog): bool
    {
        return $user->can('catalog.manage');
    }

    public function delete(User $user, DesignCatalog $designCatalog): bool
    {
        return $user->can('catalog.manage');
    }

    public function deleteAny(User $user): bool
    {
        return $user->can('catalog.manage');
    }

    public function restore(User $user, DesignCatalog $designCatalog): bool
    {
        return $user->can('catalog.manage');
    }

    public function restoreAny(User $user): bool
    {
        return $user->can('catalog.manage');
    }

    public function forceDelete(User $user, DesignCatalog $designCatalog): bool
    {
        return $user->can('catalog.manage');
    }

    public function forceDeleteAny(User $user): bool
    {
        return $user->can('catalog.manage');
    }
}
