<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\PackageIndexRequest;
use App\Http\Resources\PackageResource;
use App\Models\Package;
use App\Services\PackageReadService;
use App\Support\ApiResponder;
use Illuminate\Http\JsonResponse;

class PackageController extends Controller
{
    public function __construct(
        private readonly PackageReadService $packageReadService,
        private readonly ApiResponder $responder,
    ) {}

    public function index(PackageIndexRequest $request): JsonResponse
    {
        $payload = $request->validated();
        $perPage = (int) ($payload['per_page'] ?? 15);

        $packages = $this->packageReadService->paginateActive($payload, $perPage);

        return $this->responder->paginated($packages, PackageResource::collection($packages), 'Daftar paket berhasil dimuat.');
    }

    public function show(Package $package): JsonResponse
    {
        if (! $package->is_active) {
            return $this->responder->error('Paket tidak tersedia.', 404);
        }

        return $this->responder->success(new PackageResource($package));
    }
}
