<?php

namespace App\Services;

use App\Models\AddOn;
use App\Models\Package;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class AdminPackageService
{
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

            return $package->refresh();
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
}
