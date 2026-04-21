<?php

namespace App\Services;

use App\Enums\BookingStatus;
use App\Models\BlackoutDate;
use App\Models\Booking;
use App\Models\Package;
use App\Models\TimeSlot;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class SlotService
{
    public function getAvailability(string $date, int $packageId, int $branchId): Collection
    {
        $slotDate = Carbon::parse($date)->toDateString();

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

        return $slots->map(function (TimeSlot $slot) use ($bookings, $package, $slotDate) {
            $slotStart = Carbon::parse($slotDate.' '.$slot->start_time);
            $slotEnd = Carbon::parse($slotDate.' '.$slot->end_time);
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
                ->filter(fn (Booking $booking) => Carbon::parse($booking->start_at)->lt($sessionEnd)
                    && Carbon::parse($booking->end_at)->gt($slotStart))
                ->count();

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
}
