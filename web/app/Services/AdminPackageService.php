<?php

namespace App\Services;

use App\Enums\BookingStatus;
use App\Models\AddOn;
use App\Models\Package;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class AdminPackageService
{
    public function __construct(
        private readonly InventoryService $inventoryService,
        private readonly ActivityLogger $activityLogger,
    ) {}

    public function create(array $payload): Package
    {
        return DB::transaction(function () use ($payload): Package {
            $samplePhotos = $this->resolveSamplePhotosForCreate($payload);

            $package = Package::query()->create([
                'branch_id' => $payload['branch_id'] ?? null,
                'code' => $this->nextCode(),
                'name' => $payload['name'],
                'description' => $payload['description'] ?? null,
                'sample_photos' => $samplePhotos,
                'duration_minutes' => $payload['duration_minutes'],
                'base_price' => $payload['base_price'],
                'is_active' => $payload['is_active'] ?? true,
                'sort_order' => $payload['sort_order'] ?? 0,
            ]);

            if (array_key_exists('add_ons', $payload)) {
                $this->syncPackageAddOns($package, $payload['add_ons'] ?? []);
            }

            if (array_key_exists('inventory_items', $payload) || (bool) ($payload['inventory_items_present'] ?? false)) {
                $this->inventoryService->syncPackageConsumptions($package, $payload['inventory_items'] ?? []);
            }

            $package = $package->refresh();

            $this->activityLogger->log(
                'packages',
                'created',
                null,
                Package::class,
                (int) $package->id,
                [
                    'message' => sprintf('Paket %s dibuat.', (string) $package->name),
                    'label' => (string) $package->code,
                    'name' => (string) $package->name,
                    'branch_id' => $package->branch_id ? (int) $package->branch_id : null,
                    'duration_minutes' => (int) $package->duration_minutes,
                    'base_price' => (float) $package->base_price,
                ],
            );

            return $package;
        });
    }

    public function update(Package $package, array $payload): Package
    {
        return DB::transaction(function () use ($package, $payload): Package {
            $samplePhotos = $this->resolveSamplePhotosForUpdate($package, $payload);

            $package->update([
                'branch_id' => array_key_exists('branch_id', $payload) ? $payload['branch_id'] : $package->branch_id,
                'name' => $payload['name'],
                'description' => $payload['description'] ?? null,
                'sample_photos' => $samplePhotos,
                'duration_minutes' => $payload['duration_minutes'],
                'base_price' => $payload['base_price'],
                'is_active' => $payload['is_active'] ?? $package->is_active,
                'sort_order' => $payload['sort_order'] ?? $package->sort_order,
            ]);

            if (array_key_exists('add_ons', $payload)) {
                $this->syncPackageAddOns($package, $payload['add_ons'] ?? []);
            }

            if (array_key_exists('inventory_items', $payload) || (bool) ($payload['inventory_items_present'] ?? false)) {
                $this->inventoryService->syncPackageConsumptions($package, $payload['inventory_items'] ?? []);
            }

            $package = $package->refresh();

            $this->activityLogger->log(
                'packages',
                'updated',
                null,
                Package::class,
                (int) $package->id,
                [
                    'message' => sprintf('Paket %s diperbarui.', (string) $package->name),
                    'label' => (string) $package->code,
                    'name' => (string) $package->name,
                    'branch_id' => $package->branch_id ? (int) $package->branch_id : null,
                    'duration_minutes' => (int) $package->duration_minutes,
                    'base_price' => (float) $package->base_price,
                    'updated_fields' => array_keys($payload),
                ],
            );

            return $package;
        });
    }

    public function delete(Package $package): void
    {
        $this->activityLogger->log(
            'packages',
            'deleted',
            null,
            Package::class,
            (int) $package->id,
            [
                'message' => sprintf('Paket %s dihapus.', (string) $package->name),
                'label' => (string) $package->code,
                'name' => (string) $package->name,
            ],
        );

        $package->delete();
    }

    public function managementRows(): array
    {
        $startOfMonth = now()->startOfMonth()->toDateString();
        $endOfMonth = now()->endOfMonth()->toDateString();

        return Package::query()
            ->orderBy('sort_order')
            ->orderBy('name')
            ->with([
                'addOns:id,package_id,code,name,description,price,max_qty,is_physical,is_active,sort_order',
                'addOns.inventoryItems:id,code,name,unit,available_stock,low_stock_threshold,is_active',
                'inventoryItems:id,code,name,unit',
            ])
            ->withCount([
                'bookings as total_bookings',
                'bookings as this_month_bookings' => function (Builder $query) use ($startOfMonth, $endOfMonth): void {
                    $query->whereBetween('booking_date', [$startOfMonth, $endOfMonth]);
                },
                'bookings as pending_bookings' => function (Builder $query): void {
                    $query->whereIn('status', [
                        BookingStatus::Pending->value,
                        BookingStatus::Confirmed->value,
                        BookingStatus::Paid->value,
                        BookingStatus::CheckedIn->value,
                        BookingStatus::InQueue->value,
                        BookingStatus::InSession->value,
                    ]);
                },
                'bookings as completed_bookings' => function (Builder $query): void {
                    $query->where('status', BookingStatus::Done->value);
                },
                'addOns as add_ons_count',
            ])
            ->withSum([
                'bookings as this_month_revenue' => function (Builder $query) use ($startOfMonth, $endOfMonth): void {
                    $query->whereBetween('booking_date', [$startOfMonth, $endOfMonth]);
                },
            ], 'paid_amount')
            ->get([
                'id',
                'branch_id',
                'code',
                'name',
                'description',
                'sample_photos',
                'duration_minutes',
                'base_price',
                'is_active',
                'sort_order',
                'created_at',
                'updated_at',
            ])
            ->map(function (Package $package): array {
                $addOns = $package->addOns
                    ->sortBy([['sort_order', 'asc'], ['name', 'asc']])
                    ->values()
                    ->map(function (AddOn $addOn): array {
                        $price = (float) $addOn->price;
                        $inventoryItems = $this->inventoryService->mapAddOnInventoryItems($addOn);
                        $effectiveStock = $this->inventoryService->effectiveAvailableStock($inventoryItems);
                        $stockTone = $this->inventoryService->effectiveStockTone($inventoryItems, $effectiveStock);

                        return [
                            'id' => (int) $addOn->id,
                            'code' => (string) $addOn->code,
                            'name' => (string) $addOn->name,
                            'description' => (string) ($addOn->description ?? ''),
                            'price' => $price,
                            'price_text' => $this->formatRupiah($price),
                            'max_qty' => max(1, (int) $addOn->max_qty),
                            'is_physical' => (bool) $addOn->is_physical,
                            'inventory_items' => $inventoryItems,
                            'effective_available_stock' => $effectiveStock,
                            'effective_stock_status' => $stockTone['status'],
                            'effective_stock_label' => $stockTone['label'],
                            'is_active' => (bool) $addOn->is_active,
                            'sort_order' => (int) $addOn->sort_order,
                        ];
                    })
                    ->all();

                return [
                    'id' => (int) $package->id,
                    'branch_id' => $package->branch_id ? (int) $package->branch_id : null,
                    'code' => (string) $package->code,
                    'name' => (string) $package->name,
                    'description' => (string) ($package->description ?? ''),
                    'sample_photos' => $package->resolvedSamplePhotos(),
                    'duration_minutes' => (int) $package->duration_minutes,
                    'base_price' => (float) $package->base_price,
                    'base_price_text' => $this->formatRupiah((float) $package->base_price),
                    'is_active' => (bool) $package->is_active,
                    'sort_order' => (int) $package->sort_order,
                    'total_bookings' => (int) ($package->total_bookings ?? 0),
                    'this_month_bookings' => (int) ($package->this_month_bookings ?? 0),
                    'pending_bookings' => (int) ($package->pending_bookings ?? 0),
                    'completed_bookings' => (int) ($package->completed_bookings ?? 0),
                    'add_ons_count' => (int) ($package->add_ons_count ?? count($addOns)),
                    'add_ons' => $addOns,
                    'inventory_items' => $this->inventoryService->mapPackageInventoryItems($package),
                    'this_month_revenue' => (float) ($package->this_month_revenue ?? 0),
                    'this_month_revenue_text' => $this->formatRupiah((float) ($package->this_month_revenue ?? 0)),
                    'created_at' => $package->created_at?->toIso8601String(),
                    'updated_at' => $package->updated_at?->toIso8601String(),
                ];
            })
            ->values()
            ->all();
    }

    private function nextCode(): string
    {
        $cursor = ((int) Package::withTrashed()->max('id')) + 1;

        do {
            $candidate = sprintf('PKG-%05d', $cursor);
            $exists = Package::withTrashed()->where('code', $candidate)->exists();
            $cursor++;
        } while ($exists);

        return $candidate;
    }

    private function syncPackageAddOns(Package $package, array $rows): void
    {
        $normalizedRows = collect($rows)
            ->filter(fn ($row): bool => is_array($row))
            ->map(fn (array $row): array => [
                'id' => isset($row['id']) ? (int) $row['id'] : null,
                'name' => trim((string) ($row['name'] ?? '')),
                'description' => array_key_exists('description', $row) ? trim((string) ($row['description'] ?? '')) : null,
                'price' => (float) ($row['price'] ?? 0),
                'max_qty' => max(1, (int) ($row['max_qty'] ?? 1)),
                'is_active' => array_key_exists('is_active', $row) ? (bool) $row['is_active'] : true,
                'sort_order' => max(0, (int) ($row['sort_order'] ?? 0)),
            ])
            ->filter(fn (array $row): bool => $row['name'] !== '')
            ->values();

        $existing = $package->addOns()->get()->keyBy('id');
        $keepIds = [];

        foreach ($normalizedRows as $row) {
            $addOnId = $row['id'] ? (int) $row['id'] : null;

            if ($addOnId !== null) {
                $current = $existing->get($addOnId);

                if (! $current) {
                    throw ValidationException::withMessages([
                        'add_ons' => 'Satu atau beberapa add-on tidak sesuai dengan paket ini.',
                    ]);
                }

                $current->update([
                    'name' => $row['name'],
                    'description' => $row['description'] !== '' ? $row['description'] : null,
                    'price' => $row['price'],
                    'max_qty' => $row['max_qty'],
                    'is_active' => $row['is_active'],
                    'sort_order' => $row['sort_order'],
                ]);

                $keepIds[] = (int) $current->id;

                continue;
            }

            $created = AddOn::query()->create([
                'package_id' => (int) $package->id,
                'code' => $this->nextPackageAddOnCode($package),
                'name' => $row['name'],
                'description' => $row['description'] !== '' ? $row['description'] : null,
                'price' => $row['price'],
                'max_qty' => $row['max_qty'],
                'is_active' => $row['is_active'],
                'sort_order' => $row['sort_order'],
            ]);

            $keepIds[] = (int) $created->id;
        }

        $package->addOns()
            ->when(count($keepIds) > 0, fn ($query) => $query->whereNotIn('id', $keepIds))
            ->when(count($keepIds) === 0, fn ($query) => $query)
            ->delete();
    }

    private function nextPackageAddOnCode(Package $package): string
    {
        $cursor = 1;

        do {
            $candidate = sprintf('PKG%s-ADDON-%03d', (int) $package->id, $cursor);
            $exists = AddOn::query()->where('code', $candidate)->exists();
            $cursor++;
        } while ($exists);

        return $candidate;
    }

    private function resolveSamplePhotosForCreate(array $payload): array
    {
        $manualPhotos = $this->normalizeSamplePhotos($payload['sample_photos'] ?? []);
        $keptPhotos = $this->normalizeSamplePhotos($payload['sample_photos_keep'] ?? []);

        $basePhotos = collect([...$manualPhotos, ...$keptPhotos])
            ->unique()
            ->values()
            ->take(12)
            ->all();

        $remainingLimit = max(0, 12 - count($basePhotos));
        $uploadedPhotos = $this->storeUploadedSamplePhotos($payload['sample_photos_files'] ?? [], $remainingLimit);

        return collect([...$basePhotos, ...$uploadedPhotos])
            ->unique()
            ->values()
            ->take(12)
            ->all();
    }

    private function resolveSamplePhotosForUpdate(Package $package, array $payload): array
    {
        $currentPhotos = $this->normalizeSamplePhotos($package->sample_photos ?? []);
        $manualPhotos = $this->normalizeSamplePhotos($payload['sample_photos'] ?? []);
        $keptPhotos = $this->normalizeSamplePhotos($payload['sample_photos_keep'] ?? []);
        $keepFieldPresent = array_key_exists('sample_photos_keep', $payload)
            || ((bool) ($payload['sample_photos_keep_present'] ?? false));

        if ($keepFieldPresent || array_key_exists('sample_photos', $payload)) {
            $basePhotos = collect([...$manualPhotos, ...$keptPhotos])
                ->unique()
                ->values()
                ->take(12)
                ->all();
        } else {
            $basePhotos = $currentPhotos;
        }

        $remainingLimit = max(0, 12 - count($basePhotos));
        $uploadedPhotos = $this->storeUploadedSamplePhotos($payload['sample_photos_files'] ?? [], $remainingLimit);

        $nextPhotos = collect([...$basePhotos, ...$uploadedPhotos])
            ->unique()
            ->values()
            ->take(12)
            ->all();

        $this->deleteRemovedSamplePhotos($currentPhotos, $nextPhotos);

        return $nextPhotos;
    }

    private function storeUploadedSamplePhotos(array|UploadedFile|null $rawFiles, int $limit = 12): array
    {
        if ($limit <= 0 || $rawFiles === null) {
            return [];
        }

        $files = is_array($rawFiles) ? $rawFiles : [$rawFiles];
        $storedPhotos = [];

        foreach ($files as $file) {
            if (! $file instanceof UploadedFile) {
                continue;
            }

            if (count($storedPhotos) >= $limit) {
                break;
            }

            $storedPath = $file->store('package-samples', 'public');
            $storedPhotos[] = Package::resolveSamplePhotoUrl('/media/'.ltrim($storedPath, '/'));
        }

        return collect($storedPhotos)
            ->filter(fn ($item): bool => is_string($item) && trim($item) !== '')
            ->values()
            ->all();
    }

    private function deleteRemovedSamplePhotos(array $currentPhotos, array $nextPhotos): void
    {
        $removedPhotos = array_values(array_diff($currentPhotos, $nextPhotos));

        foreach ($removedPhotos as $photoUrl) {
            $diskPath = $this->resolvePublicDiskPath($photoUrl);

            if ($diskPath === null) {
                continue;
            }

            Storage::disk('public')->delete($diskPath);
        }
    }

    private function resolvePublicDiskPath(string $photoUrl): ?string
    {
        $urlPath = parse_url($photoUrl, PHP_URL_PATH);
        $path = is_string($urlPath) && trim($urlPath) !== '' ? $urlPath : trim($photoUrl);

        if ($path === '') {
            return null;
        }

        $normalizedPath = '/'.ltrim($path, '/');

        if (str_starts_with($normalizedPath, '/media/package-samples/')) {
            return ltrim(substr($normalizedPath, strlen('/media/')), '/');
        }

        if (str_starts_with($normalizedPath, '/storage/package-samples/')) {
            return ltrim(substr($normalizedPath, strlen('/storage/')), '/');
        }

        if (str_starts_with($normalizedPath, '/package-samples/')) {
            return ltrim($normalizedPath, '/');
        }

        return null;
    }

    private function normalizeSamplePhotos(array|string|null $raw): array
    {
        return collect(Package::resolveSamplePhotoUrls($raw))
            ->filter(fn (string $item): bool => mb_strlen($item) <= 2048)
            ->values()
            ->take(12)
            ->all();
    }

    private function formatRupiah(float $amount): string
    {
        return 'Rp ' . number_format($amount, 0, ',', '.');
    }
}
