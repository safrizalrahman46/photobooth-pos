<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Http\Requests\AdminInventoryMovementRequest;
use App\Http\Requests\AdminStoreInventoryItemRequest;
use App\Http\Requests\AdminUpdateInventoryItemRequest;
use App\Models\InventoryItem;
use App\Services\ActivityLogger;
use App\Services\AdminDashboardDataService;
use App\Services\InventoryService;
use Illuminate\Http\JsonResponse;

class AdminInventoryController extends Controller
{
    public function index(AdminDashboardDataService $service): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => $service->inventoryManagementPayload(),
        ]);
    }

    public function store(
        AdminStoreInventoryItemRequest $request,
        AdminDashboardDataService $service,
        ActivityLogger $activityLogger,
    ): JsonResponse
    {
        $payload = $request->validated();

        $inventoryItem = InventoryItem::query()->create([
            'code' => trim((string) ($payload['code'] ?? '')) ?: $this->nextCode(),
            'name' => $payload['name'],
            'unit' => trim((string) ($payload['unit'] ?? 'pcs')) ?: 'pcs',
            'available_stock' => max(0, (int) ($payload['available_stock'] ?? 0)),
            'low_stock_threshold' => max(0, (int) ($payload['low_stock_threshold'] ?? 0)),
            'is_active' => $payload['is_active'] ?? true,
            'sort_order' => $payload['sort_order'] ?? 0,
        ]);

        $activityLogger->log(
            'inventory',
            'created',
            $request->user()?->id,
            InventoryItem::class,
            (int) $inventoryItem->id,
            [
                'message' => sprintf('Inventory item %s dibuat.', (string) $inventoryItem->name),
                'label' => (string) $inventoryItem->code,
                'item_name' => (string) $inventoryItem->name,
                'available_stock' => (int) $inventoryItem->available_stock,
                'low_stock_threshold' => (int) $inventoryItem->low_stock_threshold,
            ],
        );

        return response()->json([
            'success' => true,
            'message' => 'Inventory item created successfully.',
            'data' => $service->inventoryManagementPayload(),
        ], 201);
    }

    public function update(
        AdminUpdateInventoryItemRequest $request,
        InventoryItem $inventoryItem,
        AdminDashboardDataService $service,
        ActivityLogger $activityLogger,
    ): JsonResponse {
        $payload = $request->validated();
        $codeInput = trim((string) ($payload['code'] ?? ''));

        $inventoryItem->update([
            'code' => $codeInput !== '' ? $codeInput : $inventoryItem->code,
            'name' => $payload['name'],
            'unit' => trim((string) ($payload['unit'] ?? $inventoryItem->unit)) ?: 'pcs',
            'available_stock' => array_key_exists('available_stock', $payload)
                ? max(0, (int) $payload['available_stock'])
                : (int) $inventoryItem->available_stock,
            'low_stock_threshold' => array_key_exists('low_stock_threshold', $payload)
                ? max(0, (int) $payload['low_stock_threshold'])
                : (int) $inventoryItem->low_stock_threshold,
            'is_active' => $payload['is_active'] ?? $inventoryItem->is_active,
            'sort_order' => $payload['sort_order'] ?? $inventoryItem->sort_order,
        ]);

        $activityLogger->log(
            'inventory',
            'updated',
            $request->user()?->id,
            InventoryItem::class,
            (int) $inventoryItem->id,
            [
                'message' => sprintf('Inventory item %s diperbarui.', (string) $inventoryItem->name),
                'label' => (string) $inventoryItem->code,
                'item_name' => (string) $inventoryItem->name,
                'available_stock' => (int) $inventoryItem->available_stock,
                'low_stock_threshold' => (int) $inventoryItem->low_stock_threshold,
                'updated_fields' => array_keys($payload),
            ],
        );

        return response()->json([
            'success' => true,
            'message' => 'Inventory item updated successfully.',
            'data' => $service->inventoryManagementPayload(),
        ]);
    }

    public function destroy(
        InventoryItem $inventoryItem,
        AdminDashboardDataService $service,
        ActivityLogger $activityLogger,
    ): JsonResponse
    {
        $activityLogger->log(
            'inventory',
            'deleted',
            request()->user()?->id,
            InventoryItem::class,
            (int) $inventoryItem->id,
            [
                'message' => sprintf('Inventory item %s dihapus.', (string) $inventoryItem->name),
                'label' => (string) $inventoryItem->code,
                'item_name' => (string) $inventoryItem->name,
            ],
        );

        $inventoryItem->delete();

        return response()->json([
            'success' => true,
            'message' => 'Inventory item deleted successfully.',
            'data' => $service->inventoryManagementPayload(),
        ]);
    }

    public function movement(
        AdminInventoryMovementRequest $request,
        InventoryItem $inventoryItem,
        InventoryService $inventoryService,
        AdminDashboardDataService $service,
    ): JsonResponse {
        $actorId = (int) ($request->user()?->id ?? 0);

        $inventoryService->recordMovement(
            $inventoryItem,
            $request->validated(),
            $actorId > 0 ? $actorId : null
        );

        return response()->json([
            'success' => true,
            'message' => 'Inventory movement saved successfully.',
            'data' => $service->inventoryManagementPayload(),
        ]);
    }

    private function nextCode(): string
    {
        $cursor = ((int) InventoryItem::query()->max('id')) + 1;

        do {
            $candidate = sprintf('INV-%05d', $cursor);
            $exists = InventoryItem::query()->where('code', $candidate)->exists();
            $cursor++;
        } while ($exists);

        return $candidate;
    }
}
