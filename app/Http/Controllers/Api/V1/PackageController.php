<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\StorePackageRequest;
use App\Http\Requests\UpdatePackageRequest;
use App\Http\Requests\PackageIndexRequest;
use App\Http\Resources\PackageResource;
use App\Models\Package;
use App\Support\ApiResponder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PackageController extends Controller
{
    public function __construct(
        private readonly ApiResponder $responder,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $perPage = min((int) $request->integer('per_page', 15), 100);

        $query = Package::query()
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('name');

        if ($request->filled('branch_id')) {
            $branchId = (int) $request->integer('branch_id');
            $query->where(function ($builder) use ($branchId) {
                $builder->where('branch_id', $branchId)
                    ->orWhereNull('branch_id');
            });
        }

        $packages = $query->paginate($perPage)->withQueryString();

        return $this->responder->paginated($packages, PackageResource::collection($packages), 'Daftar paket berhasil dimuat.');
    }

    public function show(Package $package): JsonResponse
    {
        if (! $package->is_active) {
            return $this->responder->error('Paket tidak tersedia.', 404);
        }

        return $this->responder->success(new PackageResource($package));
    }

    public function adminIndex(Request $request): JsonResponse
    {
        abort_unless($request->user()?->can('catalog.manage') || $request->user()?->hasRole('owner'), 403);

        $perPage = min((int) $request->integer('per_page', 50), 200);

        $query = Package::query()
            ->orderBy('sort_order')
            ->orderBy('name');

        if (! $request->boolean('include_inactive', true)) {
            $query->where('is_active', true);
        }

        if ($request->filled('branch_id')) {
            $query->where('branch_id', $request->integer('branch_id'));
        }

        if ($request->filled('search')) {
            $search = trim($request->string('search')->toString());
            $query->where(function ($builder) use ($search) {
                $builder->where('name', 'like', "%{$search}%")
                    ->orWhere('code', 'like', "%{$search}%");
            });
        }

        $packages = $query->paginate($perPage)->withQueryString();

        return $this->responder->paginated($packages, PackageResource::collection($packages), 'Daftar paket manajemen berhasil dimuat.');
    }

    public function store(StorePackageRequest $request): JsonResponse
    {
        $payload = $request->validated();

        $package = Package::query()->create([
            'branch_id' => $payload['branch_id'] ?? null,
            'code' => $payload['code'],
            'name' => $payload['name'],
            'description' => $payload['description'] ?? null,
            'duration_minutes' => $payload['duration_minutes'],
            'base_price' => $payload['base_price'],
            'is_active' => $payload['is_active'] ?? true,
            'sort_order' => $payload['sort_order'] ?? 0,
        ]);

        return $this->responder->success(new PackageResource($package), 'Paket berhasil dibuat.', 201);
    }

    public function update(UpdatePackageRequest $request, Package $package): JsonResponse
    {
        $payload = $request->validated();

        $package->fill([
            'branch_id' => $payload['branch_id'] ?? null,
            'code' => $payload['code'],
            'name' => $payload['name'],
            'description' => $payload['description'] ?? null,
            'duration_minutes' => $payload['duration_minutes'],
            'base_price' => $payload['base_price'],
            'is_active' => $payload['is_active'] ?? true,
            'sort_order' => $payload['sort_order'] ?? 0,
        ]);

        $package->save();

        return $this->responder->success(new PackageResource($package), 'Paket berhasil diperbarui.');
    }
}
