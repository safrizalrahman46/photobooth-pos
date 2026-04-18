<?php

namespace App\Services;

use App\Enums\BookingStatus;
use App\Enums\QueueSourceType;
use App\Enums\QueueStatus;
use App\Models\Booking;
use App\Models\QueueTicket;
use App\Support\CodeGenerator;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class QueueService
{
    public function __construct(
        private readonly CodeGenerator $codeGenerator,
    ) {}

    public function checkInBooking(Booking $booking): QueueTicket
    {
        return DB::transaction(function () use ($booking): QueueTicket {
            $date = Carbon::parse($booking->booking_date);
            $queueNumber = $this->nextQueueNumber((int) $booking->branch_id, $date->toDateString());

            $ticket = QueueTicket::query()->create([
                'queue_code' => $this->codeGenerator->generateQueueCode($date, $queueNumber),
                'branch_id' => $booking->branch_id,
                'queue_date' => $date->toDateString(),
                'queue_number' => $queueNumber,
                'source_type' => QueueSourceType::Booking,
                'booking_id' => $booking->id,
                'customer_name' => $booking->customer_name,
                'customer_phone' => $booking->customer_phone,
                'status' => QueueStatus::Waiting,
                'priority' => 1,
            ]);

            $booking->status = BookingStatus::InQueue;
            $booking->save();

            return $ticket;
        });
    }

    public function checkInByBookingId(int $bookingId): QueueTicket
    {
        $booking = Booking::query()
            ->with('queueTicket:id,booking_id')
            ->findOrFail($bookingId);

        if ($booking->queueTicket) {
            throw ValidationException::withMessages([
                'booking_id' => 'Booking ini sudah memiliki tiket antrean.',
            ]);
        }

        $allowedStatuses = [
            BookingStatus::Pending->value,
            BookingStatus::Confirmed->value,
            BookingStatus::Paid->value,
            BookingStatus::CheckedIn->value,
        ];

        $bookingStatus = (string) ($booking->status?->value ?? $booking->status);

        if (! in_array($bookingStatus, $allowedStatuses, true)) {
            throw ValidationException::withMessages([
                'booking_id' => 'Status booking tidak dapat ditambahkan ke antrean.',
            ]);
        }

        return $this->checkInBooking($booking);
    }

    public function createWalkIn(array $payload): QueueTicket
    {
        return DB::transaction(function () use ($payload): QueueTicket {
            $date = Carbon::parse($payload['queue_date']);
            $queueNumber = $this->nextQueueNumber((int) $payload['branch_id'], $date->toDateString());

            return QueueTicket::query()->create([
                'queue_code' => $this->codeGenerator->generateQueueCode($date, $queueNumber),
                'branch_id' => $payload['branch_id'],
                'queue_date' => $date->toDateString(),
                'queue_number' => $queueNumber,
                'source_type' => QueueSourceType::WalkIn,
                'customer_name' => $payload['customer_name'],
                'customer_phone' => $payload['customer_phone'] ?? null,
                'status' => QueueStatus::Waiting,
                'priority' => 0,
            ]);
        });
    }

    public function transition(QueueTicket $ticket, QueueStatus $toStatus): QueueTicket
    {
        $now = now();

        $ticket->status = $toStatus;

        if ($toStatus === QueueStatus::Called) {
            $ticket->called_at = $now;
        }

        if ($toStatus === QueueStatus::CheckedIn) {
            $ticket->checked_in_at = $now;
        }

        if ($toStatus === QueueStatus::InSession) {
            $ticket->started_at = $now;
        }

        if ($toStatus === QueueStatus::Finished) {
            $ticket->finished_at = $now;
            if ($ticket->booking) {
                $ticket->booking->status = BookingStatus::Done;
                $ticket->booking->save();
            }
        }

        if ($toStatus === QueueStatus::Skipped) {
            $ticket->skipped_at = $now;
        }

        if ($toStatus === QueueStatus::Cancelled) {
            $ticket->cancelled_at = $now;
        }

        $ticket->save();

        return $ticket->refresh();
    }

    public function callNext(int $branchId, string $date): ?QueueTicket
    {
        $ticket = QueueTicket::query()
            ->where('branch_id', $branchId)
            ->whereDate('queue_date', $date)
            ->where('status', QueueStatus::Waiting)
            ->orderByDesc('priority')
            ->orderBy('queue_number')
            ->first();

        if (! $ticket) {
            return null;
        }

        return $this->transition($ticket, QueueStatus::Called);
    }

    public function callNextForBranch(int $branchId, ?string $queueDate = null): ?QueueTicket
    {
        $date = $queueDate ?: now()->toDateString();

        return $this->callNext($branchId, $date);
    }

    public function createWalkInFromPayload(array $payload): QueueTicket
    {
        return $this->createWalkIn([
            'branch_id' => (int) $payload['branch_id'],
            'queue_date' => (string) ($payload['queue_date'] ?? now()->toDateString()),
            'customer_name' => (string) $payload['customer_name'],
            'customer_phone' => $payload['customer_phone'] ?? null,
        ]);
    }

    private function nextQueueNumber(int $branchId, string $queueDate): int
    {
        $maxQueue = QueueTicket::query()
            ->where('branch_id', $branchId)
            ->whereDate('queue_date', $queueDate)
            ->lockForUpdate()
            ->max('queue_number');

        return ((int) $maxQueue) + 1;
    }
}
