<?php

namespace App\Policies;

use App\Models\Booking;
use App\Models\User;

class BookingPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('booking.view');
    }

    public function view(User $user, Booking $booking): bool
    {
        return $user->can('booking.view');
    }

    public function create(User $user): bool
    {
        return $user->can('booking.manage');
    }

    public function update(User $user, Booking $booking): bool
    {
        return $user->can('booking.manage');
    }

    public function delete(User $user, Booking $booking): bool
    {
        return $user->can('booking.manage');
    }

    public function deleteAny(User $user): bool
    {
        return $user->can('booking.manage');
    }

    public function restore(User $user, Booking $booking): bool
    {
        return $user->can('booking.manage');
    }

    public function restoreAny(User $user): bool
    {
        return $user->can('booking.manage');
    }

    public function forceDelete(User $user, Booking $booking): bool
    {
        return $user->can('booking.manage');
    }

    public function forceDeleteAny(User $user): bool
    {
        return $user->can('booking.manage');
    }
}
