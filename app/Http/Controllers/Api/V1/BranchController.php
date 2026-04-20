<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreBranchRequest;
use App\Http\Requests\UpdateBranchRequest;
use App\Http\Resources\BranchResource;
use App\Models\Branch;
use App\Support\ApiResponder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BranchController extends Controller
{
    public function __construct(
        private readonly ApiResponder $responder,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $query = Branch::query()->orderBy('name');

        $includeInactive = $request->boolean('include_inactive');
        $canIncludeInactive = $request->user()?->can('settings.manage') || $request->user()?->hasRole('owner');

        if (! ($includeInactive && $canIncludeInactive)) {
            $query->where('is_active', true);
        }

        $branches = $query->get();

        return $this->responder->success(BranchResource::collection($branches), 'Daftar cabang berhasil dimuat.');
    }

    public function show(Request $request, Branch $branch): JsonResponse
    {
        $canViewInactive = $request->user()?->can('settings.manage') || $request->user()?->hasRole('owner');

        if (! $branch->is_active && ! $canViewInactive) {
            return $this->responder->error('Cabang tidak tersedia.', 404);
        }

        return $this->responder->success(new BranchResource($branch));
    }

    public function adminIndex(Request $request): JsonResponse
    {
        abort_unless($request->user()?->can('settings.manage') || $request->user()?->hasRole('owner'), 403);

        $query = Branch::query()->orderBy('name');

        if (! $request->boolean('include_inactive', true)) {
            $query->where('is_active', true);
        }

        if ($request->filled('search')) {
            $search = trim($request->string('search')->toString());
            $query->where(function ($builder) use ($search) {
                $builder->where('name', 'like', "%{$search}%")
                    ->orWhere('code', 'like', "%{$search}%");
            });
        }

        $branches = $query->get();

        return $this->responder->success(BranchResource::collection($branches), 'Daftar cabang manajemen berhasil dimuat.');
    }

    public function store(StoreBranchRequest $request): JsonResponse
    {
        $payload = $request->validated();

        $branch = Branch::query()->create([
            'code' => $payload['code'],
            'name' => $payload['name'],
            'timezone' => $payload['timezone'],
            'phone' => $payload['phone'] ?? null,
            'address' => $payload['address'] ?? null,
            'is_active' => $payload['is_active'] ?? true,
        ]);

        return $this->responder->success(new BranchResource($branch), 'Cabang berhasil dibuat.', 201);
    }

    public function update(UpdateBranchRequest $request, Branch $branch): JsonResponse
    {
        $payload = $request->validated();

        $branch->fill([
            'code' => $payload['code'],
            'name' => $payload['name'],
            'timezone' => $payload['timezone'],
            'phone' => $payload['phone'] ?? null,
            'address' => $payload['address'] ?? null,
            'is_active' => $payload['is_active'] ?? true,
        ]);

        $branch->save();

        return $this->responder->success(new BranchResource($branch), 'Cabang berhasil diperbarui.');
    }
}
