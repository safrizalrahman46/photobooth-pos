<?php

namespace App\Policies;

use App\Models\QueueTicket;
use App\Models\User;

class QueueTicketPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('queue.view');
    }

    public function view(User $user, QueueTicket $queueTicket): bool
    {
        return $user->can('queue.view');
    }

    public function create(User $user): bool
    {
        return $user->can('queue.manage');
    }

    public function update(User $user, QueueTicket $queueTicket): bool
    {
        return $user->can('queue.manage');
    }

    public function delete(User $user, QueueTicket $queueTicket): bool
    {
        return $user->can('queue.manage');
    }

    public function deleteAny(User $user): bool
    {
        return $user->can('queue.manage');
    }

    public function restore(User $user, QueueTicket $queueTicket): bool
    {
        return $user->can('queue.manage');
    }

    public function restoreAny(User $user): bool
    {
        return $user->can('queue.manage');
    }

    public function forceDelete(User $user, QueueTicket $queueTicket): bool
    {
        return $user->can('queue.manage');
    }

    public function forceDeleteAny(User $user): bool
    {
        return $user->can('queue.manage');
    }
}
