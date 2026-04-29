<?php

namespace App\Services;

use App\Models\Branch;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class AdminBranchService
{
    public function __construct(
        private readonly AppSettingService $appSettingService,
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
            'is_active' => (bool) ($payload['is_active'] ?? true),
        ]);

        $this->appSettingService->settingsPayload();

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
            'is_active' => $nextActive,
        ]);

        $branch->save();

        $this->appSettingService->settingsPayload();

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

            return 'deactivated';
        }

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

