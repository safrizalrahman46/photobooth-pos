<?php

namespace App\Services;

use App\Models\TimeSlot;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Validation\ValidationException;

class AdminTimeSlotService
{
    public function rows(array $filters = []): array
    {
        $query = TimeSlot::query()
            ->with('branch:id,name')
            ->orderByDesc('slot_date')
            ->orderBy('start_time');

        if (! empty($filters['branch_id'])) {
            $query->where('branch_id', (int) $filters['branch_id']);
        }

        if (! empty($filters['slot_date'])) {
            $query->whereDate('slot_date', (string) $filters['slot_date']);
        }

        if (array_key_exists('is_bookable', $filters) && $filters['is_bookable'] !== null && $filters['is_bookable'] !== '') {
            $query->where('is_bookable', (bool) $filters['is_bookable']);
        }

        return $query
            ->get()
            ->map(fn (TimeSlot $slot): array => $this->mapRow($slot))
            ->values()
            ->all();
    }

    public function create(array $payload): TimeSlot
    {
        $this->ensureNoOverlap(
            (int) $payload['branch_id'],
            (string) $payload['slot_date'],
            (string) $payload['start_time'],
            (string) $payload['end_time'],
        );

        return TimeSlot::query()->create([
            'branch_id' => (int) $payload['branch_id'],
            'slot_date' => (string) $payload['slot_date'],
            'start_time' => (string) $payload['start_time'],
            'end_time' => (string) $payload['end_time'],
            'capacity' => (int) $payload['capacity'],
            'is_bookable' => (bool) ($payload['is_bookable'] ?? true),
        ]);
    }

    public function update(TimeSlot $timeSlot, array $payload): TimeSlot
    {
        $this->ensureNoOverlap(
            (int) $payload['branch_id'],
            (string) $payload['slot_date'],
            (string) $payload['start_time'],
            (string) $payload['end_time'],
            (int) $timeSlot->id,
        );

        $timeSlot->fill([
            'branch_id' => (int) $payload['branch_id'],
            'slot_date' => (string) $payload['slot_date'],
            'start_time' => (string) $payload['start_time'],
            'end_time' => (string) $payload['end_time'],
            'capacity' => (int) $payload['capacity'],
            'is_bookable' => (bool) ($payload['is_bookable'] ?? true),
        ]);

        $timeSlot->save();

        return $timeSlot->refresh();
    }

    public function destroy(TimeSlot $timeSlot): void
    {
        $timeSlot->delete();
    }

    public function bulkBookable(array $slotIds, bool $isBookable): int
    {
        return TimeSlot::query()
            ->whereIn('id', $slotIds)
            ->update([
                'is_bookable' => $isBookable,
                'updated_at' => now(),
            ]);
    }

    public function generate(array $payload): array
    {
        $startDate = Carbon::parse((string) $payload['start_date'])->startOfDay();
        $endDate = Carbon::parse((string) $payload['end_date'])->startOfDay();
        $interval = (int) $payload['interval_minutes'];
        $branchId = (int) $payload['branch_id'];
        $capacity = (int) $payload['capacity'];
        $isBookable = (bool) ($payload['is_bookable'] ?? true);

        $createdCount = 0;
        $skippedCount = 0;

        for ($date = $startDate->copy(); $date->lte($endDate); $date->addDay()) {
            $dateString = $date->toDateString();
            $cursor = Carbon::parse($dateString.' '.(string) $payload['day_start_time']);
            $dayEnd = Carbon::parse($dateString.' '.(string) $payload['day_end_time']);

            while ($cursor->copy()->addMinutes($interval)->lte($dayEnd)) {
                $slotStart = $cursor->copy();
                $slotEnd = $cursor->copy()->addMinutes($interval);
                $startTime = $slotStart->format('H:i:s');
                $endTime = $slotEnd->format('H:i:s');

                if ($this->hasOverlap($branchId, $dateString, $startTime, $endTime)) {
                    $skippedCount++;
                    $cursor->addMinutes($interval);
                    continue;
                }

                TimeSlot::query()->create([
                    'branch_id' => $branchId,
                    'slot_date' => $dateString,
                    'start_time' => $startTime,
                    'end_time' => $endTime,
                    'capacity' => $capacity,
                    'is_bookable' => $isBookable,
                ]);

                $createdCount++;
                $cursor->addMinutes($interval);
            }
        }

        return [
            'created_count' => $createdCount,
            'skipped_count' => $skippedCount,
            'branch_id' => $branchId,
            'start_date' => $startDate->toDateString(),
            'end_date' => $endDate->toDateString(),
        ];
    }

    private function ensureNoOverlap(
        int $branchId,
        string $slotDate,
        string $startTime,
        string $endTime,
        ?int $ignoreId = null,
    ): void {
        if (! $this->hasOverlap($branchId, $slotDate, $startTime, $endTime, $ignoreId)) {
            return;
        }

        throw ValidationException::withMessages([
            'start_time' => 'Rentang waktu slot bertabrakan dengan slot lain pada tanggal yang sama.',
        ]);
    }

    private function hasOverlap(
        int $branchId,
        string $slotDate,
        string $startTime,
        string $endTime,
        ?int $ignoreId = null,
    ): bool {
        $query = TimeSlot::query()
            ->where('branch_id', $branchId)
            ->whereDate('slot_date', $slotDate)
            ->where('start_time', '<', $endTime)
            ->where('end_time', '>', $startTime);

        if ($ignoreId !== null) {
            $query->whereKeyNot($ignoreId);
        }

        return $query->exists();
    }

    private function mapRow(TimeSlot $slot): array
    {
        return [
            'id' => (int) $slot->id,
            'branch_id' => (int) $slot->branch_id,
            'branch_name' => (string) ($slot->branch?->name ?? '-'),
            'slot_date' => $slot->slot_date?->toDateString(),
            'slot_date_text' => $slot->slot_date?->format('d M Y') ?? '-',
            'start_time' => (string) $slot->start_time,
            'start_time_text' => substr((string) $slot->start_time, 0, 5),
            'end_time' => (string) $slot->end_time,
            'end_time_text' => substr((string) $slot->end_time, 0, 5),
            'capacity' => (int) $slot->capacity,
            'is_bookable' => (bool) $slot->is_bookable,
            'created_at' => $slot->created_at?->toIso8601String(),
            'updated_at' => $slot->updated_at?->toIso8601String(),
        ];
    }
}

