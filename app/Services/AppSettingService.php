<?php

namespace App\Services;

use App\Models\AppSetting;
use App\Models\Branch;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Str;

class AppSettingService
{
    public const KEY_DEFAULT_BRANCH = 'booking.default_branch';

    public function getDefaultBranchId(bool $fallbackToFirstActive = false): ?int
    {
        $settingBranchId = $this->configuredDefaultBranchId();

        if ($settingBranchId !== null) {
            return $settingBranchId;
        }

        if (! $fallbackToFirstActive) {
            return null;
        }

        $firstActiveBranchId = Branch::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->value('id');

        return $firstActiveBranchId ? (int) $firstActiveBranchId : null;
    }

    public function settingsPayload(): array
    {
        $branches = Branch::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'code', 'name', 'timezone', 'phone', 'address'])
            ->map(fn (Branch $branch): array => [
                'id' => (int) $branch->id,
                'code' => (string) $branch->code,
                'name' => (string) $branch->name,
                'timezone' => (string) $branch->timezone,
                'phone' => (string) ($branch->phone ?? ''),
                'address' => (string) ($branch->address ?? ''),
            ])
            ->values()
            ->all();

        return [
            'default_branch_id' => $this->getDefaultBranchId(true),
            'branches' => $branches,
        ];
    }

    public function updateDefaultBranch(int $branchId, ?int $updatedBy = null): array
    {
        $branch = Branch::query()
            ->where('is_active', true)
            ->findOrFail($branchId, ['id']);

        AppSetting::query()->updateOrCreate(
            ['key' => self::KEY_DEFAULT_BRANCH],
            [
                'value' => [
                    'branch_id' => (int) $branch->id,
                ],
                'updated_by' => $updatedBy,
                'updated_at' => now(),
            ],
        );

        return $this->settingsPayload();
    }

    public function createBranch(array $payload, ?int $updatedBy = null): array
    {
        $name = trim((string) ($payload['name'] ?? ''));

        $branch = Branch::query()->create([
            'code' => $this->generateBranchCode($name),
            'name' => $name,
            'timezone' => (string) ($payload['timezone'] ?? 'Asia/Jakarta'),
            'phone' => $this->toNullableString($payload['phone'] ?? null),
            'address' => $this->toNullableString($payload['address'] ?? null),
            'is_active' => true,
        ]);

        if ($this->configuredDefaultBranchId() === null) {
            AppSetting::query()->updateOrCreate(
                ['key' => self::KEY_DEFAULT_BRANCH],
                [
                    'value' => ['branch_id' => (int) $branch->id],
                    'updated_by' => $updatedBy,
                    'updated_at' => now(),
                ],
            );
        }

        return $this->settingsPayload();
    }

    public function updateBranch(Branch $branch, array $payload): array
    {
        if (! $branch->is_active) {
            throw ValidationException::withMessages([
                'branch' => 'Cabang tidak aktif dan tidak dapat diubah.',
            ]);
        }

        $branch->update([
            'name' => trim((string) ($payload['name'] ?? $branch->name)),
            'timezone' => (string) ($payload['timezone'] ?? $branch->timezone),
            'phone' => $this->toNullableString($payload['phone'] ?? $branch->phone),
            'address' => $this->toNullableString($payload['address'] ?? $branch->address),
        ]);

        return $this->settingsPayload();
    }

    public function deactivateBranch(Branch $branch, ?int $updatedBy = null): array
    {
        if (! $branch->is_active) {
            return $this->settingsPayload();
        }

        $activeCount = (int) Branch::query()->where('is_active', true)->count();

        if ($activeCount <= 1) {
            throw ValidationException::withMessages([
                'branch' => 'Minimal harus ada 1 cabang aktif.',
            ]);
        }

        $branch->update([
            'is_active' => false,
        ]);

        $currentDefaultBranchId = $this->configuredDefaultBranchId();

        if ($currentDefaultBranchId !== (int) $branch->id) {
            return $this->settingsPayload();
        }

        $nextDefaultBranchId = $this->getDefaultBranchId(true);

        if ($nextDefaultBranchId === null) {
            return $this->settingsPayload();
        }

        AppSetting::query()->updateOrCreate(
            ['key' => self::KEY_DEFAULT_BRANCH],
            [
                'value' => ['branch_id' => $nextDefaultBranchId],
                'updated_by' => $updatedBy,
                'updated_at' => now(),
            ],
        );

        return $this->settingsPayload();
    }

    private function generateBranchCode(string $name): string
    {
        $base = Str::upper(Str::substr(Str::of($name)->replaceMatches('/[^A-Za-z0-9]+/', '')->value(), 0, 8));
        $base = $base !== '' ? $base : 'BRANCH';

        $candidate = $base;
        $counter = 1;

        while (Branch::query()->where('code', $candidate)->exists()) {
            $candidate = Str::upper(Str::substr($base, 0, 6)).str_pad((string) $counter, 2, '0', STR_PAD_LEFT);
            $counter++;
        }

        return $candidate;
    }

    private function toNullableString(mixed $value): ?string
    {
        $text = trim((string) $value);

        return $text !== '' ? $text : null;
    }

    private function configuredDefaultBranchId(): ?int
    {
        $setting = AppSetting::query()->find(self::KEY_DEFAULT_BRANCH, ['key', 'value']);

        $branchId = (int) data_get($setting?->value ?? [], 'branch_id');

        if ($branchId <= 0) {
            return null;
        }

        $isValid = Branch::query()
            ->where('is_active', true)
            ->whereKey($branchId)
            ->exists();

        return $isValid ? $branchId : null;
    }
}