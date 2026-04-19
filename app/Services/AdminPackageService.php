<?php

namespace App\Services;

use App\Models\AddOn;
use App\Models\Package;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class AdminPackageService
{
    public function create(array $payload): Package
    {
        return DB::transaction(function () use ($payload): Package {
            $package = Package::query()->create([
                'branch_id' => $payload['branch_id'] ?? null,
                'code' => $this->nextCode(),
                'name' => $payload['name'],
                'description' => $payload['description'] ?? null,
                'duration_minutes' => $payload['duration_minutes'],
                'base_price' => $payload['base_price'],
                'is_active' => $payload['is_active'] ?? true,
                'sort_order' => $payload['sort_order'] ?? 0,
            ]);

            if (array_key_exists('add_ons', $payload)) {
                $this->syncPackageAddOns($package, $payload['add_ons'] ?? []);
            }

            return $package->refresh();
        });
    }

    public function update(Package $package, array $payload): Package
    {
        return DB::transaction(function () use ($package, $payload): Package {
            $package->update([
                'branch_id' => array_key_exists('branch_id', $payload) ? $payload['branch_id'] : $package->branch_id,
                'name' => $payload['name'],
                'description' => $payload['description'] ?? null,
                'duration_minutes' => $payload['duration_minutes'],
                'base_price' => $payload['base_price'],
                'is_active' => $payload['is_active'] ?? $package->is_active,
                'sort_order' => $payload['sort_order'] ?? $package->sort_order,
            ]);

            if (array_key_exists('add_ons', $payload)) {
                $this->syncPackageAddOns($package, $payload['add_ons'] ?? []);
            }

            return $package->refresh();
        });
    }

    public function delete(Package $package): void
    {
        $package->delete();
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
                        'add_ons' => 'One or more selected add-ons are invalid for this package.',
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
}
