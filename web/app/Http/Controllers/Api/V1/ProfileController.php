<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\UserProfileResource;
use App\Support\ApiResponder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class ProfileController extends Controller
{
    public function __construct(
        private readonly ApiResponder $responder,
    ) {}

    public function show(Request $request): JsonResponse
    {
        $user = $request->user()->loadMissing('roles', 'permissions');

        return $this->responder->success(new UserProfileResource($user));
    }

    public function update(Request $request): JsonResponse
    {
        $user = $request->user();
        $validated = $request->validate([
            'name' => ['sometimes', 'string', 'max:120'],
            'phone' => ['sometimes', 'nullable', 'string', 'max:30'],
            'password' => ['sometimes', 'string', 'min:8', 'confirmed'],
        ]);

        if (array_key_exists('name', $validated)) {
            $user->name = $validated['name'];
        }

        if (array_key_exists('phone', $validated)) {
            $user->phone = $validated['phone'];
        }

        if (array_key_exists('password', $validated)) {
            $user->password = Hash::make($validated['password']);
        }

        $user->save();

        return $this->responder->success(
            new UserProfileResource($user->loadMissing('roles', 'permissions')),
            'Profil berhasil diperbarui.'
        );
    }
}
