<?php

namespace App\Services;

use App\Enums\BookingSource;
use App\Enums\BookingStatus;
use App\Jobs\SendBookingNotificationJob;
use App\Models\Booking;
use App\Models\BookingStatusLog;
use App\Models\Package;
use App\Support\CodeGenerator;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class BookingService
{
    public function __construct(
        private readonly CodeGenerator $codeGenerator,
    ) {}

    public function create(array $payload): Booking
    {
        return DB::transaction(function () use ($payload): Booking {
            $package = Package::query()->findOrFail($payload['package_id']);
            $startAt = Carbon::parse($payload['booking_date'].' '.$payload['booking_time']);
            $endAt = $startAt->copy()->addMinutes((int) $package->duration_minutes);

            $this->assertNoConflict(
                (int) $payload['branch_id'],
                $startAt,
                $endAt
            );

            $booking = Booking::query()->create([
                'booking_code' => $this->codeGenerator->generateBookingCode($startAt),
                'branch_id' => $payload['branch_id'],
                'package_id' => $payload['package_id'],
                'design_catalog_id' => $payload['design_catalog_id'] ?? null,
                'customer_name' => $payload['customer_name'],
                'customer_phone' => $payload['customer_phone'],
                'customer_email' => $payload['customer_email'] ?? null,
                'booking_date' => $startAt->toDateString(),
                'start_at' => $startAt,
                'end_at' => $endAt,
                'status' => BookingStatus::Pending,
                'source' => $payload['source'] ?? BookingSource::Web,
                'total_amount' => $package->base_price,
                'notes' => $payload['notes'] ?? null,
            ]);

            BookingStatusLog::query()->create([
                'booking_id' => $booking->id,
                'from_status' => null,
                'to_status' => BookingStatus::Pending->value,
                'reason' => 'Booking created',
            ]);

            SendBookingNotificationJob::dispatch($booking->id, 'created');

            return $booking;
        });
    }

    public function updateStatus(Booking $booking, BookingStatus $toStatus, ?int $actorId = null, ?string $reason = null): Booking
    {
        return DB::transaction(function () use ($booking, $toStatus, $actorId, $reason): Booking {
            $fromStatus = $booking->status instanceof BookingStatus
                ? $booking->status
                : BookingStatus::from($booking->status);

            $booking->status = $toStatus;
            $booking->save();

            BookingStatusLog::query()->create([
                'booking_id' => $booking->id,
                'from_status' => $fromStatus->value,
                'to_status' => $toStatus->value,
                'changed_by' => $actorId,
                'reason' => $reason,
            ]);

            SendBookingNotificationJob::dispatch($booking->id, 'status_changed');

            return $booking->refresh();
        });
    }

    private function assertNoConflict(int $branchId, Carbon $startAt, Carbon $endAt): void
    {
        $hasConflict = Booking::query()
            ->where('branch_id', $branchId)
            ->whereIn('status', BookingStatus::activeStatuses())
            ->where(function ($query) use ($startAt, $endAt) {
                $query->where('start_at', '<', $endAt)
                    ->where('end_at', '>', $startAt);
            })
            ->lockForUpdate()
            ->exists();

        if ($hasConflict) {
            throw new RuntimeException('Slot sudah terisi. Silakan pilih jadwal lain.');
        }
    }
}
