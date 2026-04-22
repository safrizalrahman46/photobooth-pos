<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\LoginRequest;
use App\Http\Resources\UserProfileResource;
use App\Models\User;
use App\Support\ApiResponder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    public function __construct(
        private readonly ApiResponder $responder,
    ) {}

    public function login(LoginRequest $request): JsonResponse
    {
        $payload = $request->validated();

        $user = User::query()->where('email', $payload['email'])->first();

        if (! $user || ! Hash::check($payload['password'], $user->password)) {
            return $this->responder->error('Email atau password tidak valid.', 422);
        }

        if (! $user->is_active) {
            return $this->responder->error('Akun tidak aktif.', 403);
        }

        $deviceName = $payload['device_name'] ?? $request->userAgent() ?? 'api-device';
        $token = $user->createToken($deviceName)->plainTextToken;

        $user->forceFill(['last_login_at' => now()])->save();

        return $this->responder->success([
            'token' => $token,
            'token_type' => 'Bearer',
            'user' => new UserProfileResource($user->loadMissing('roles', 'permissions')),
        ], 'Login berhasil.');
    }

    public function logout(Request $request): JsonResponse
    {
        $token = $request->user()?->currentAccessToken();

        if ($token) {
            $token->delete();
        }

        return $this->responder->success(null, 'Logout berhasil.');
    }
}
