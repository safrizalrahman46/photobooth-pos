<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class ApiAuthService
{
    public function login(array $payload, ?string $userAgent = null): ?array
    {
        $user = User::query()->where('email', $payload['email'])->first();

        if (! $user || ! Hash::check($payload['password'], $user->password)) {
            return null;
        }

        if (! $user->is_active) {
            return [
                'error' => 'Akun tidak aktif.',
                'status' => 403,
            ];
        }

        $deviceName = $payload['device_name'] ?? $userAgent ?? 'api-device';
        $token = $user->createToken($deviceName)->plainTextToken;

        $user->forceFill(['last_login_at' => now()])->save();

        return [
            'token' => $token,
            'token_type' => 'Bearer',
            'user' => $user->loadMissing('roles', 'permissions'),
        ];
    }

    public function logout(Request $request): void
    {
        $token = $request->user()?->currentAccessToken();

        if ($token) {
            $token->delete();
        }
    }
}
