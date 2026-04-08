<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
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
}
