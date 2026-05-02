<?php

namespace App\Services;

use App\Enums\BookingStatus;
use App\Models\Booking;
use App\Models\Package;
use App\Models\TimeSlot;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;

class AdminTimeSlotService
{
    public function __construct(
        private readonly ActivityLogger $activityLogger,
        private readonly SlotService $slotService,
    ) {}

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

        $slots = $query->get();

        if ($slots->isEmpty()) {
            return [];
        }

        $branchIds = $slots->pluck('branch_id')
            ->map(fn ($id): int => (int) $id)
            ->filter(fn (int $id): bool => $id > 0)
            ->unique()
            ->values();

        $slotDates = $slots->map(fn (TimeSlot $slot): ?string => $slot->slot_date?->toDateString())
            ->filter()
            ->unique()
            ->values();

        $bookingsByKey = Booking::query()
            ->whereIn('branch_id', $branchIds->all())
            ->whereIn('booking_date', $slotDates->all())
            ->whereIn('status', BookingStatus::activeStatuses())
            ->get(['id', 'branch_id', 'booking_date', 'start_at', 'end_at'])
            ->groupBy(function (Booking $booking): string {
                return (int) $booking->branch_id . '|' . ($booking->booking_date?->toDateString() ?? '');
            });

        $packages = Package::query()
            ->where('is_active', true)
            ->where(function (Builder $query) use ($branchIds): void {
                $query->whereNull('branch_id')
                    ->orWhereIn('branch_id', $branchIds->all());
            })
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get(['id', 'branch_id', 'name', 'duration_minutes']);

        return $slots
            ->map(function (TimeSlot $slot) use ($bookingsByKey, $packages): array {
                $slotDate = $slot->slot_date?->toDateString() ?? '';
                $branchId = (int) $slot->branch_id;
                $bookingKey = $branchId . '|' . $slotDate;
                $branchPackages = $packages
                    ->filter(fn (Package $package): bool => $package->branch_id === null || (int) $package->branch_id === $branchId)
                    ->values();

                return $this->mapRow(
                    $slot,
                    $bookingsByKey->get($bookingKey, collect()),
                    $branchPackages,
                );
            })
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

        $timeSlot = TimeSlot::query()->create([
            'branch_id' => (int) $payload['branch_id'],
            'slot_date' => (string) $payload['slot_date'],
            'start_time' => (string) $payload['start_time'],
            'end_time' => (string) $payload['end_time'],
            'capacity' => (int) $payload['capacity'],
            'is_bookable' => (bool) ($payload['is_bookable'] ?? true),
        ]);

        $this->activityLogger->log(
            'time-slots',
            'created',
            null,
            TimeSlot::class,
            (int) $timeSlot->id,
            [
                'message' => sprintf('Time slot %s %s-%s dibuat.', (string) $timeSlot->slot_date, (string) $timeSlot->start_time, (string) $timeSlot->end_time),
                'label' => (string) $timeSlot->slot_date,
                'branch_id' => (int) $timeSlot->branch_id,
                'slot_date' => (string) $timeSlot->slot_date,
                'start_time' => (string) $timeSlot->start_time,
                'end_time' => (string) $timeSlot->end_time,
                'capacity' => (int) $timeSlot->capacity,
                'is_bookable' => (bool) $timeSlot->is_bookable,
            ],
        );

        return $timeSlot;
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

        $this->ensureCapacitySupportsActiveBookings(
            (int) $payload['branch_id'],
            (string) $payload['slot_date'],
            (string) $payload['start_time'],
            (string) $payload['end_time'],
            (int) $payload['capacity'],
            null,
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

        $this->activityLogger->log(
            'time-slots',
            'updated',
            null,
            TimeSlot::class,
            (int) $timeSlot->id,
            [
                'message' => sprintf('Time slot %s %s-%s diperbarui.', (string) $timeSlot->slot_date, (string) $timeSlot->start_time, (string) $timeSlot->end_time),
                'label' => (string) $timeSlot->slot_date,
                'branch_id' => (int) $timeSlot->branch_id,
                'slot_date' => (string) $timeSlot->slot_date,
                'start_time' => (string) $timeSlot->start_time,
                'end_time' => (string) $timeSlot->end_time,
                'capacity' => (int) $timeSlot->capacity,
                'is_bookable' => (bool) $timeSlot->is_bookable,
                'updated_fields' => array_keys($payload),
            ],
        );

        return $timeSlot->refresh();
    }

    public function destroy(TimeSlot $timeSlot): void
    {
        $this->ensureSlotHasNoActiveBookings($timeSlot);

        $this->activityLogger->log(
            'time-slots',
            'deleted',
            null,
            TimeSlot::class,
            (int) $timeSlot->id,
            [
                'message' => sprintf('Time slot %s %s-%s dihapus.', (string) $timeSlot->slot_date, (string) $timeSlot->start_time, (string) $timeSlot->end_time),
                'label' => (string) $timeSlot->slot_date,
                'branch_id' => (int) $timeSlot->branch_id,
            ],
        );

        $timeSlot->delete();
    }

    public function bulkBookable(array $slotIds, bool $isBookable): int
    {
        $updatedCount = TimeSlot::query()
            ->whereIn('id', $slotIds)
            ->update([
                'is_bookable' => $isBookable,
                'updated_at' => now(),
            ]);

        if ($updatedCount > 0) {
            $this->activityLogger->log(
                'time-slots',
                'bulk_bookable_updated',
                null,
                TimeSlot::class,
                null,
                [
                    'message' => sprintf('Status bookable %d time slot diperbarui.', $updatedCount),
                    'label' => 'bulk-update',
                    'slot_count' => $updatedCount,
                    'is_bookable' => $isBookable,
                ],
            );
        }

        return $updatedCount;
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

        $this->activityLogger->log(
            'time-slots',
            'generated',
            null,
            TimeSlot::class,
            null,
            [
                'message' => sprintf('Generate time slot selesai: %d dibuat, %d dilewati.', $createdCount, $skippedCount),
                'label' => sprintf('%s - %s', $startDate->toDateString(), $endDate->toDateString()),
                'branch_id' => $branchId,
                'created_count' => $createdCount,
                'skipped_count' => $skippedCount,
                'is_bookable' => $isBookable,
            ],
        );

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

    private function mapRow(TimeSlot $slot, Collection $bookings, Collection $packages): array
    {
        $slotDate = $slot->slot_date?->toDateString() ?? null;
        $timezone = $this->slotService->branchTimezone((int) $slot->branch_id);
        $slotStart = $this->slotService->slotStartAt($slot, $timezone, $slotDate);
        $slotEnd = $this->slotService->slotEndAt($slot, $timezone, $slotDate);
        $slotDurationMinutes = $this->slotService->slotDurationMinutes($slot, $timezone, $slotDate);
        $activeBookingsCount = $this->slotService->overlapCountFromCollection($bookings, $slotStart, $slotEnd);
        $remainingParallelCapacity = max(0, (int) $slot->capacity - $activeBookingsCount);
        $compatiblePackages = $packages
            ->filter(fn (Package $package): bool => $this->slotService->packageFitsSlot($slot, $package, $timezone, $slotDate))
            ->values();
        $compatiblePackageNames = $compatiblePackages
            ->take(4)
            ->map(fn (Package $package): string => (string) $package->name)
            ->values()
            ->all();

        return [
            'id' => (int) $slot->id,
            'branch_id' => (int) $slot->branch_id,
            'branch_name' => (string) ($slot->branch?->name ?? '-'),
            'slot_date' => $slotDate,
            'slot_date_text' => $slot->slot_date?->format('d M Y') ?? '-',
            'start_time' => (string) $slot->start_time,
            'start_time_text' => substr((string) $slot->start_time, 0, 5),
            'end_time' => (string) $slot->end_time,
            'end_time_text' => substr((string) $slot->end_time, 0, 5),
            'capacity' => (int) $slot->capacity,
            'is_bookable' => (bool) $slot->is_bookable,
            'slot_duration_minutes' => $slotDurationMinutes,
            'active_bookings_count' => $activeBookingsCount,
            'remaining_parallel_capacity' => $remainingParallelCapacity,
            'is_full' => $remainingParallelCapacity <= 0,
            'compatible_packages_count' => $compatiblePackages->count(),
            'compatible_package_names' => $compatiblePackageNames,
            'longest_supported_duration_minutes' => (int) ($compatiblePackages->max('duration_minutes') ?? 0),
            'created_at' => $slot->created_at?->toIso8601String(),
            'updated_at' => $slot->updated_at?->toIso8601String(),
        ];
    }

    private function ensureCapacitySupportsActiveBookings(
        int $branchId,
        string $slotDate,
        string $startTime,
        string $endTime,
        int $capacity,
        ?int $exceptBookingId = null,
    ): void {
        $timezone = $this->slotService->branchTimezone($branchId);
        $startAt = Carbon::parse($slotDate . ' ' . $startTime, $timezone);
        $endAt = Carbon::parse($slotDate . ' ' . $endTime, $timezone);
        $activeOverlapCount = $this->slotService->overlapCount($branchId, $startAt, $endAt, $exceptBookingId);

        if ($activeOverlapCount <= $capacity) {
            return;
        }

        throw ValidationException::withMessages([
            'capacity' => sprintf('Kapasitas tidak boleh lebih kecil dari %d booking aktif yang sudah overlap pada slot ini.', $activeOverlapCount),
        ]);
    }

    private function ensureSlotHasNoActiveBookings(TimeSlot $timeSlot): void
    {
        $slotDate = $timeSlot->slot_date?->toDateString() ?? now()->toDateString();
        $timezone = $this->slotService->branchTimezone((int) $timeSlot->branch_id);
        $startAt = Carbon::parse($slotDate . ' ' . $timeSlot->start_time, $timezone);
        $endAt = Carbon::parse($slotDate . ' ' . $timeSlot->end_time, $timezone);
        $activeOverlapCount = $this->slotService->overlapCount((int) $timeSlot->branch_id, $startAt, $endAt);

        if ($activeOverlapCount <= 0) {
            return;
        }

        throw ValidationException::withMessages([
            'time_slot' => 'Slot ini masih dipakai booking aktif. Nonaktifkan booking pada slot ini terlebih dahulu, jangan hapus langsung.',
        ]);
    }
}
