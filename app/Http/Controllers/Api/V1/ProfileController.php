<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\ProfileUpdateRequest;
use App\Http\Resources\UserProfileResource;
use App\Services\ProfileService;
use App\Support\ApiResponder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProfileController extends Controller
{
    public function __construct(
        private readonly ProfileService $profileService,
        private readonly ApiResponder $responder,
    ) {}

    public function show(Request $request): JsonResponse
    {
        $user = $request->user()->loadMissing('roles', 'permissions');

        return $this->responder->success(new UserProfileResource($user));
    }

    public function update(ProfileUpdateRequest $request): JsonResponse
    {
        $user = $request->user();
        $updatedUser = $this->profileService->update($user, $request->validated());

        return $this->responder->success(
            new UserProfileResource($updatedUser->loadMissing('roles', 'permissions')),
            'Profil berhasil diperbarui.'
        );
    }
}
