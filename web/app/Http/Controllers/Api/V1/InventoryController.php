<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\InventoryItemResource;
use App\Models\InventoryItem;
use App\Services\InventoryService;
use App\Support\ApiResponder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class InventoryController extends Controller
{
    public function __construct(
        private readonly ApiResponder $responder,
    ) {}

    public function adminIndex(Request $request): JsonResponse
    {
        abort_unless($request->user()?->can('catalog.manage') || $request->user()?->hasRole('owner'), 403);

        $perPage = min((int) $request->integer('per_page', 100), 200);

        $query = InventoryItem::query()
            ->orderBy('sort_order')
            ->orderBy('name');

        if (! $request->boolean('include_inactive', true)) {
            $query->where('is_active', true);
        }

        if ($request->filled('search')) {
            $search = trim($request->string('search')->toString());
            $query->where(function ($builder) use ($search): void {
                $builder->where('name', 'like', "%{$search}%")
                    ->orWhere('code', 'like', "%{$search}%");
            });
        }

        $items = $query->paginate($perPage)->withQueryString();

        return $this->responder->paginated($items, InventoryItemResource::collection($items), 'Daftar inventory item berhasil dimuat.');
    }

    public function monitoring(Request $request, InventoryService $service): JsonResponse
    {
        abort_unless($this->canViewInventory($request), 403);

        return $this->responder->success(
            $service->managementPayload(),
            'Data monitoring stok berhasil dimuat.'
        );
    }

    private function canViewInventory(Request $request): bool
    {
        $user = $request->user();

        return (bool) (
            $user?->can('inventory.view')
            || $user?->can('catalog.manage')
            || $user?->hasRole('owner')
            || $user?->hasRole('admin')
            || $user?->hasRole('cashier')
        );
    }
}
