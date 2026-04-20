<?php

namespace App\Services;

use App\Models\User;

class AdminUserService
{
    public function create(array $payload): User
    {
        $user = User::query()->create([
            'name' => $payload['name'],
            'email' => $payload['email'],
            'phone' => $payload['phone'] ?? null,
            'password' => $payload['password'],
            'is_active' => $payload['is_active'] ?? true,
        ]);

        if (! empty($payload['role'])) {
            $user->syncRoles([$payload['role']]);
        }

        return $user->refresh();
    }
}
