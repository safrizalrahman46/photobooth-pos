<?php

namespace App\Services;

use App\Enums\BookingStatus;
use App\Models\BlackoutDate;
use App\Models\Branch;
use App\Models\Booking;
use App\Models\Package;
use App\Models\TimeSlot;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class SlotService
{
    private const MAX_ACTIVE_BOOKINGS_PER_SLOT = 1;

    public function getAvailability(string $date, int $packageId, int $branchId): Collection
    {
        $slotDate = Carbon::parse($date)->toDateString();
        $branchTimezone = Branch::query()
            ->whereKey($branchId)
            ->value('timezone');
        $timezone = is_string($branchTimezone) && trim($branchTimezone) !== ''
            ? $branchTimezone
            : config('app.timezone', 'UTC');

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

        return $slots->map(function (TimeSlot $slot) use ($bookings, $package, $slotDate, $timezone) {
            $slotStart = Carbon::createFromFormat('Y-m-d H:i:s', $slotDate.' '.$slot->start_time, $timezone);
            $slotEnd = Carbon::createFromFormat('Y-m-d H:i:s', $slotDate.' '.$slot->end_time, $timezone);
            $sessionEnd = $slotStart->copy()->addMinutes((int) $package->duration_minutes);

            if ($sessionEnd->gt($slotEnd)) {
                return [
                    'slot_id' => $slot->id,
                    'start_time' => $slot->start_time,
                    'end_time' => $slot->end_time,
                    'remaining_slots' => 0,
                    'is_available' => false,
                ];
            }

            $overlapCount = $bookings
                ->filter(function (Booking $booking) use ($sessionEnd, $slotStart, $timezone): bool {
                    $bookingStart = Carbon::parse($booking->start_at)->setTimezone($timezone);
                    $bookingEnd = Carbon::parse($booking->end_at)->setTimezone($timezone);

                    return $bookingStart->lt($sessionEnd)
                        && $bookingEnd->gt($slotStart);
                })
                ->count();

            $remaining = max(0, self::MAX_ACTIVE_BOOKINGS_PER_SLOT - $overlapCount);

            return [
                'slot_id' => $slot->id,
                'start_time' => $slot->start_time,
                'end_time' => $slot->end_time,
                'remaining_slots' => $remaining,
                'is_available' => $remaining > 0,
            ];
        });
    }
}
