<?php

namespace App\Services;

use App\Enums\BookingStatus;
use App\Models\BlackoutDate;
use App\Models\Booking;
use App\Models\Branch;
use App\Models\Package;
use App\Models\TimeSlot;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class SlotService
{
    public function getAvailability(string $date, int $packageId, int $branchId): Collection
    {
        $slotDate = Carbon::parse($date)->toDateString();
        $branchTimezone = $this->branchTimezone($branchId);
        $nowInBranch = now($branchTimezone);
        $isTodayInBranch = $nowInBranch->toDateString() === $slotDate;

        $isClosed = BlackoutDate::query()
            ->where('branch_id', $branchId)
            ->whereDate('blackout_date', $slotDate)
            ->where('is_closed', true)
            ->exists();

        if ($isClosed) {
            return collect();
        }

        $package = Package::query()->findOrFail($packageId);

        $slots = TimeSlot::query()
            ->where('branch_id', $branchId)
            ->whereDate('slot_date', $slotDate)
            ->where('is_bookable', true)
            ->orderBy('start_time')
            ->get();

        $bookings = Booking::query()
            ->where('branch_id', $branchId)
            ->whereDate('booking_date', $slotDate)
            ->whereIn('status', BookingStatus::activeStatuses())
            ->get(['start_at', 'end_at']);

        return $slots->map(function (TimeSlot $slot) use ($bookings, $package, $slotDate, $branchTimezone, $nowInBranch, $isTodayInBranch) {
            $slotStart = $this->slotStartAt($slot, $branchTimezone, $slotDate);
            $slotEnd = $this->slotEndAt($slot, $branchTimezone, $slotDate);
            $sessionEnd = $slotStart->copy()->addMinutes((int) $package->duration_minutes);

            if ($isTodayInBranch && $slotStart->lte($nowInBranch)) {
                return [
                    'slot_id' => $slot->id,
                    'start_time' => $slot->start_time,
                    'end_time' => $slot->end_time,
                    'remaining_slots' => 0,
                    'is_available' => false,
                ];
            }

            if ($sessionEnd->gt($slotEnd)) {
                return [
                    'slot_id' => $slot->id,
                    'start_time' => $slot->start_time,
                    'end_time' => $slot->end_time,
                    'remaining_slots' => 0,
                    'is_available' => false,
                ];
            }

            $overlapCount = $this->overlapCountFromCollection($bookings, $slotStart, $sessionEnd);

            $remaining = max(0, (int) $slot->capacity - $overlapCount);

            return [
                'slot_id' => $slot->id,
                'start_time' => $slot->start_time,
                'end_time' => $slot->end_time,
                'remaining_slots' => $remaining,
                'is_available' => $remaining > 0,
            ];
        });
    }

    public function branchTimezone(int $branchId): string
    {
        return (string) (Branch::query()->whereKey($branchId)->value('timezone') ?: config('app.queue_timezone', 'Asia/Jakarta'));
    }

    public function slotStartAt(TimeSlot $slot, ?string $timezone = null, ?string $slotDate = null): Carbon
    {
        $resolvedTimezone = $timezone ?: $this->branchTimezone((int) $slot->branch_id);
        $resolvedDate = $slotDate ?: $slot->slot_date?->toDateString() ?: now($resolvedTimezone)->toDateString();

        return Carbon::parse($resolvedDate . ' ' . $slot->start_time, $resolvedTimezone);
    }

    public function slotEndAt(TimeSlot $slot, ?string $timezone = null, ?string $slotDate = null): Carbon
    {
        $resolvedTimezone = $timezone ?: $this->branchTimezone((int) $slot->branch_id);
        $resolvedDate = $slotDate ?: $slot->slot_date?->toDateString() ?: now($resolvedTimezone)->toDateString();

        return Carbon::parse($resolvedDate . ' ' . $slot->end_time, $resolvedTimezone);
    }

    public function slotDurationMinutes(TimeSlot $slot, ?string $timezone = null, ?string $slotDate = null): int
    {
        return max(0, $this->slotStartAt($slot, $timezone, $slotDate)->diffInMinutes($this->slotEndAt($slot, $timezone, $slotDate), false));
    }

    public function resolveBookableSlotForSession(
        int $branchId,
        Carbon $startAt,
        Carbon $endAt,
        bool $lockForUpdate = false,
    ): ?TimeSlot {
        $query = TimeSlot::query()
            ->where('branch_id', $branchId)
            ->whereDate('slot_date', $startAt->toDateString())
            ->where('is_bookable', true)
            ->where('start_time', '<=', $startAt->format('H:i:s'))
            ->where('end_time', '>=', $endAt->format('H:i:s'))
            ->orderBy('start_time');

        if ($lockForUpdate) {
            $query->lockForUpdate();
        }

        return $query->first();
    }

    public function overlapCount(
        int $branchId,
        Carbon $startAt,
        Carbon $endAt,
        ?int $exceptBookingId = null,
        bool $lockForUpdate = false,
    ): int {
        $query = $this->overlapQuery($branchId, $startAt, $endAt, $exceptBookingId);

        if ($lockForUpdate) {
            return (int) $query
                ->lockForUpdate()
                ->get(['id'])
                ->count();
        }

        return (int) $query->count();
    }

    public function remainingParallelCapacity(
        TimeSlot $slot,
        Carbon $startAt,
        Carbon $endAt,
        ?int $exceptBookingId = null,
        bool $lockForUpdate = false,
    ): int {
        $overlapCount = $this->overlapCount((int) $slot->branch_id, $startAt, $endAt, $exceptBookingId, $lockForUpdate);

        return max(0, (int) $slot->capacity - $overlapCount);
    }

    public function packageFitsSlot(TimeSlot $slot, Package $package, ?string $timezone = null, ?string $slotDate = null): bool
    {
        $slotStart = $this->slotStartAt($slot, $timezone, $slotDate);
        $slotEnd = $this->slotEndAt($slot, $timezone, $slotDate);
        $sessionEnd = $slotStart->copy()->addMinutes((int) $package->duration_minutes);

        return $sessionEnd->lte($slotEnd);
    }

    public function overlapCountFromCollection(Collection $bookings, Carbon $startAt, Carbon $endAt): int
    {
        return $bookings
            ->filter(fn (Booking $booking) => Carbon::parse($booking->start_at)->lt($endAt)
                && Carbon::parse($booking->end_at)->gt($startAt))
            ->count();
    }

    protected function overlapQuery(
        int $branchId,
        Carbon $startAt,
        Carbon $endAt,
        ?int $exceptBookingId = null,
    ): Builder {
        return Booking::query()
            ->where('branch_id', $branchId)
            ->whereIn('status', BookingStatus::activeStatuses())
            ->when($exceptBookingId !== null, fn (Builder $query) => $query->whereKeyNot($exceptBookingId))
            ->where(function (Builder $query) use ($startAt, $endAt): void {
                $query->where('start_at', '<', $endAt)
                    ->where('end_at', '>', $startAt);
            });
    }
}
