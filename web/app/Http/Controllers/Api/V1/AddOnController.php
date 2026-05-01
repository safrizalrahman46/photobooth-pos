<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\AddOnResource;
use App\Models\AddOn;
use App\Support\ApiResponder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AddOnController extends Controller
{
    public function __construct(
        private readonly ApiResponder $responder,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $perPage = min((int) $request->integer('per_page', 100), 200);

        $query = AddOn::query()
            ->with('inventoryItems:id,code,name,unit,available_stock,low_stock_threshold,is_active')
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('name');

        if ($request->filled('package_id')) {
            $packageId = (int) $request->integer('package_id');
            $query->where(function ($builder) use ($packageId): void {
                $builder->where('package_id', $packageId)
                    ->orWhereNull('package_id');
            });
        }

        $addOns = $query->paginate($perPage)->withQueryString();

        return $this->responder->paginated($addOns, AddOnResource::collection($addOns), 'Daftar add-on berhasil dimuat.');
    }

    public function adminIndex(Request $request): JsonResponse
    {
        abort_unless($request->user()?->can('catalog.manage') || $request->user()?->hasRole('owner'), 403);

        $perPage = min((int) $request->integer('per_page', 100), 200);

        $query = AddOn::query()
            ->with('inventoryItems:id,code,name,unit,available_stock,low_stock_threshold,is_active')
            ->orderBy('sort_order')
            ->orderBy('name');

        if (! $request->boolean('include_inactive', true)) {
            $query->where('is_active', true);
        }

        if ($request->filled('package_id')) {
            $query->where('package_id', $request->integer('package_id'));
        }

        if ($request->filled('search')) {
            $search = trim($request->string('search')->toString());
            $query->where(function ($builder) use ($search): void {
                $builder->where('name', 'like', "%{$search}%")
                    ->orWhere('code', 'like', "%{$search}%");
            });
        }

        $addOns = $query->paginate($perPage)->withQueryString();

        return $this->responder->paginated($addOns, AddOnResource::collection($addOns), 'Daftar add-on manajemen berhasil dimuat.');
    }
}
