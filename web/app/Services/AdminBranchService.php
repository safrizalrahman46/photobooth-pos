<?php

namespace App\Services;

use App\Models\Branch;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class AdminBranchService
{
    public function __construct(
        private readonly AppSettingService $appSettingService,
        private readonly ActivityLogger $activityLogger,
    ) {}

    public function rows(array $filters = []): array
    {
        $query = Branch::query()
            ->withCount(['bookings', 'timeSlots', 'transactions', 'queueTickets'])
            ->orderBy('name');

        if (! ($filters['include_inactive'] ?? true)) {
            $query->where('is_active', true);
        }

        if (! empty($filters['search'])) {
            $search = trim((string) $filters['search']);

            $query->where(function ($builder) use ($search): void {
                $builder->where('name', 'like', "%{$search}%")
                    ->orWhere('code', 'like', "%{$search}%");
            });
        }

        return $query
            ->get()
            ->map(function (Branch $branch): array {
                return [
                    'id' => (int) $branch->id,
                    'code' => (string) $branch->code,
                    'name' => (string) $branch->name,
                    'timezone' => (string) $branch->timezone,
                    'phone' => (string) ($branch->phone ?? ''),
                    'address' => (string) ($branch->address ?? ''),
                    'payment_qr_url' => (string) ($branch->payment_qr_url ?? ''),
                    'is_active' => (bool) $branch->is_active,
                    'bookings_count' => (int) ($branch->bookings_count ?? 0),
                    'time_slots_count' => (int) ($branch->time_slots_count ?? 0),
                    'transactions_count' => (int) ($branch->transactions_count ?? 0),
                    'queue_tickets_count' => (int) ($branch->queue_tickets_count ?? 0),
                    'created_at' => $branch->created_at?->toIso8601String(),
                    'updated_at' => $branch->updated_at?->toIso8601String(),
                ];
            })
            ->values()
            ->all();
    }

    public function create(array $payload): Branch
    {
        $branch = Branch::query()->create([
            'code' => ! empty($payload['code']) ? (string) $payload['code'] : $this->generateBranchCode((string) $payload['name']),
            'name' => (string) $payload['name'],
            'timezone' => (string) ($payload['timezone'] ?? 'Asia/Jakarta'),
            'phone' => ! empty($payload['phone']) ? (string) $payload['phone'] : null,
            'address' => ! empty($payload['address']) ? (string) $payload['address'] : null,
            'payment_qr_url' => ! empty($payload['payment_qr_url']) ? (string) $payload['payment_qr_url'] : null,
            'is_active' => (bool) ($payload['is_active'] ?? true),
        ]);

        $this->appSettingService->settingsPayload();

        $this->activityLogger->log(
            'branches',
            'created',
            null,
            Branch::class,
            (int) $branch->id,
            [
                'message' => sprintf('Branch %s dibuat.', (string) $branch->name),
                'label' => (string) $branch->code,
                'branch_name' => (string) $branch->name,
                'timezone' => (string) $branch->timezone,
                'payment_qr_url' => (string) ($branch->payment_qr_url ?? ''),
                'is_active' => (bool) $branch->is_active,
            ],
        );

        return $branch;
    }

    public function update(Branch $branch, array $payload): Branch
    {
        $nextActive = (bool) ($payload['is_active'] ?? true);

        if (! $nextActive) {
            $this->ensureNotLastActiveBranch($branch);
        }

        $branch->fill([
            'code' => (string) $payload['code'],
            'name' => (string) $payload['name'],
            'timezone' => (string) ($payload['timezone'] ?? 'Asia/Jakarta'),
            'phone' => ! empty($payload['phone']) ? (string) $payload['phone'] : null,
            'address' => ! empty($payload['address']) ? (string) $payload['address'] : null,
            'payment_qr_url' => ! empty($payload['payment_qr_url']) ? (string) $payload['payment_qr_url'] : null,
            'is_active' => $nextActive,
        ]);

        $branch->save();

        $this->appSettingService->settingsPayload();

        $this->activityLogger->log(
            'branches',
            'updated',
            null,
            Branch::class,
            (int) $branch->id,
            [
                'message' => sprintf('Branch %s diperbarui.', (string) $branch->name),
                'label' => (string) $branch->code,
                'branch_name' => (string) $branch->name,
                'timezone' => (string) $branch->timezone,
                'payment_qr_url' => (string) ($branch->payment_qr_url ?? ''),
                'is_active' => (bool) $branch->is_active,
                'updated_fields' => array_keys($payload),
            ],
        );

        return $branch->refresh();
    }

    public function destroy(Branch $branch): string
    {
        $this->ensureNotLastActiveBranch($branch);

        $hasRelations = $branch->bookings()->exists()
            || $branch->queueTickets()->exists()
            || $branch->timeSlots()->exists()
            || $branch->transactions()->exists();

        if ($hasRelations) {
            $branch->is_active = false;
            $branch->save();
            $this->appSettingService->settingsPayload();

            $this->activityLogger->log(
                'branches',
                'deactivated',
                null,
                Branch::class,
                (int) $branch->id,
                [
                    'message' => sprintf('Branch %s dinonaktifkan karena masih memiliki relasi.', (string) $branch->name),
                    'label' => (string) $branch->code,
                    'branch_name' => (string) $branch->name,
                ],
            );

            return 'deactivated';
        }

        $this->activityLogger->log(
            'branches',
            'deleted',
            null,
            Branch::class,
            (int) $branch->id,
            [
                'message' => sprintf('Branch %s dihapus.', (string) $branch->name),
                'label' => (string) $branch->code,
                'branch_name' => (string) $branch->name,
            ],
        );

        $branch->delete();
        $this->appSettingService->settingsPayload();

        return 'deleted';
    }

    private function ensureNotLastActiveBranch(Branch $branch): void
    {
        if (! $branch->is_active) {
            return;
        }

        $activeCount = (int) Branch::query()->where('is_active', true)->count();

        if ($activeCount > 1) {
            return;
        }

        throw ValidationException::withMessages([
            'branch' => 'At least one active branch is required.',
        ]);
    }

    private function generateBranchCode(string $name): string
    {
        $normalized = strtoupper(Str::of($name)->ascii()->replaceMatches('/[^A-Z0-9]+/', '')->toString());
        $prefix = substr($normalized, 0, 6);
        $prefix = $prefix !== '' ? $prefix : 'BRANCH';
        $index = 1;

        while (true) {
            $candidate = sprintf('%s-%02d', $prefix, $index);

            if (! Branch::query()->where('code', $candidate)->exists()) {
                return $candidate;
            }

            $index++;
        }
    }
}
