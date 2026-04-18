<?php

namespace App\Services;

use App\Models\User;

class ProfileService
{
    public function update(User $user, array $payload): User
    {
        if (array_key_exists('name', $payload)) {
            $user->name = $payload['name'];
        }

        if (array_key_exists('phone', $payload)) {
            $user->phone = $payload['phone'];
        }

        if (array_key_exists('password', $payload)) {
            $user->password = $payload['password'];
        }

        $user->save();

        return $user->refresh();
    }
}
