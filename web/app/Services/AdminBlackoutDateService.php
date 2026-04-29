<?php

namespace App\Services;

use App\Models\BlackoutDate;
use Illuminate\Validation\ValidationException;

class AdminBlackoutDateService
{
    public function rows(array $filters = []): array
    {
        $query = BlackoutDate::query()
            ->with('branch:id,name')
            ->orderByDesc('blackout_date');

        if (! empty($filters['branch_id'])) {
            $query->where('branch_id', (int) $filters['branch_id']);
        }

        if (! empty($filters['blackout_date'])) {
            $query->whereDate('blackout_date', (string) $filters['blackout_date']);
        }

        if (array_key_exists('is_closed', $filters) && $filters['is_closed'] !== null && $filters['is_closed'] !== '') {
            $query->where('is_closed', (bool) $filters['is_closed']);
        }

        if (! empty($filters['search'])) {
            $search = trim((string) $filters['search']);
            $query->where('reason', 'like', "%{$search}%");
        }

        return $query
            ->get()
            ->map(fn (BlackoutDate $blackout): array => $this->mapRow($blackout))
            ->values()
            ->all();
    }

    public function create(array $payload): BlackoutDate
    {
        $this->ensureUnique((int) $payload['branch_id'], (string) $payload['blackout_date']);

        return BlackoutDate::query()->create([
            'branch_id' => (int) $payload['branch_id'],
            'blackout_date' => (string) $payload['blackout_date'],
            'reason' => ! empty($payload['reason']) ? (string) $payload['reason'] : null,
            'is_closed' => (bool) ($payload['is_closed'] ?? true),
        ]);
    }

    public function update(BlackoutDate $blackoutDate, array $payload): BlackoutDate
    {
        $this->ensureUnique(
            (int) $payload['branch_id'],
            (string) $payload['blackout_date'],
            (int) $blackoutDate->id,
        );

        $blackoutDate->fill([
            'branch_id' => (int) $payload['branch_id'],
            'blackout_date' => (string) $payload['blackout_date'],
            'reason' => ! empty($payload['reason']) ? (string) $payload['reason'] : null,
            'is_closed' => (bool) ($payload['is_closed'] ?? true),
        ]);

        $blackoutDate->save();

        return $blackoutDate->refresh();
    }

    public function destroy(BlackoutDate $blackoutDate): void
    {
        $blackoutDate->delete();
    }

    private function ensureUnique(int $branchId, string $blackoutDate, ?int $ignoreId = null): void
    {
        $query = BlackoutDate::query()
            ->where('branch_id', $branchId)
            ->whereDate('blackout_date', $blackoutDate);

        if ($ignoreId !== null) {
            $query->whereKeyNot($ignoreId);
        }

        if (! $query->exists()) {
            return;
        }

        throw ValidationException::withMessages([
            'blackout_date' => 'Tanggal blackout untuk cabang ini sudah terdaftar.',
        ]);
    }

    private function mapRow(BlackoutDate $blackoutDate): array
    {
        return [
            'id' => (int) $blackoutDate->id,
            'branch_id' => (int) $blackoutDate->branch_id,
            'branch_name' => (string) ($blackoutDate->branch?->name ?? '-'),
            'blackout_date' => $blackoutDate->blackout_date?->toDateString(),
            'blackout_date_text' => $blackoutDate->blackout_date?->format('d M Y') ?? '-',
            'reason' => (string) ($blackoutDate->reason ?? ''),
            'is_closed' => (bool) $blackoutDate->is_closed,
            'created_at' => $blackoutDate->created_at?->toIso8601String(),
            'updated_at' => $blackoutDate->updated_at?->toIso8601String(),
        ];
    }
}

