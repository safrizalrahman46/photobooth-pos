<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\LoginRequest;
use App\Http\Resources\UserProfileResource;
use App\Services\ApiAuthService;
use App\Support\ApiResponder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AuthController extends Controller
{
    public function __construct(
        private readonly ApiAuthService $apiAuthService,
        private readonly ApiResponder $responder,
    ) {}

    public function login(LoginRequest $request): JsonResponse
    {
        $payload = $request->validated();

        $result = $this->apiAuthService->login($payload, $request->userAgent());

        if ($result === null) {
            return $this->responder->error('Email atau password tidak valid.', 422);
        }

        if (isset($result['error'])) {
            return $this->responder->error((string) $result['error'], (int) ($result['status'] ?? 422));
        }

        return $this->responder->success([
            'token' => (string) $result['token'],
            'token_type' => (string) $result['token_type'],
            'user' => new UserProfileResource($result['user']),
        ], 'Login berhasil.');
    }

    public function logout(Request $request): JsonResponse
    {
        $this->apiAuthService->logout($request);

        return $this->responder->success(null, 'Logout berhasil.');
    }
}
