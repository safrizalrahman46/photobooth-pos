<?php

namespace App\Services;

use App\Enums\BookingStatus;
use App\Enums\QueueSourceType;
use App\Enums\QueueStatus;
use App\Models\Booking;
use App\Models\Branch;
use App\Models\QueueTicket;
use App\Support\CodeGenerator;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class QueueService
{
    public function __construct(
        private readonly CodeGenerator $codeGenerator,
    ) {}

    public function checkInBooking(Booking $booking): QueueTicket
    {
        return DB::transaction(function () use ($booking): QueueTicket {
            $date = Carbon::parse($booking->booking_date);

            if ($date->toDateString() !== now()->toDateString()) {
                throw new RuntimeException('Check-in booking hanya dapat dilakukan pada hari yang sama.');
            }

            if (in_array($booking->status?->value ?? $booking->status, [
                BookingStatus::Cancelled->value,
                BookingStatus::Done->value,
            ], true)) {
                throw new RuntimeException('Status booking tidak dapat dimasukkan ke antrean.');
            }

            if (! in_array($booking->status?->value ?? $booking->status, [
                BookingStatus::Confirmed->value,
                BookingStatus::Paid->value,
            ], true)) {
                throw new RuntimeException('Booking harus diverifikasi admin sebelum check-in antrean.');
            }

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
        $fromStatus = $ticket->status instanceof QueueStatus
            ? $ticket->status
            : QueueStatus::from((string) $ticket->status);

        $this->assertTransitionAllowed($fromStatus, $toStatus);

        $now = now();

        $ticket->status = $toStatus;

        if ($toStatus === QueueStatus::Called) {
            $ticket->called_at ??= $now;
        }

        if ($toStatus === QueueStatus::CheckedIn) {
            $ticket->checked_in_at ??= $now;
        }

        if ($toStatus === QueueStatus::InSession) {
            $ticket->started_at ??= $now;
        }

        if ($toStatus === QueueStatus::Finished) {
            $ticket->finished_at ??= $now;
        }

        if ($toStatus === QueueStatus::Skipped) {
            $ticket->skipped_at = $now;
        }

        if ($toStatus === QueueStatus::Cancelled) {
            $ticket->cancelled_at = $now;
        }

        $ticket->save();

        $this->syncBookingStatus($ticket, $toStatus);

        return $ticket->refresh();
    }

    public function callNext(int $branchId, string $date): ?QueueTicket
    {
        $ticket = QueueTicket::query()
            ->where('branch_id', $branchId)
            ->where('queue_date', $date)
            ->where('status', QueueStatus::Waiting)
            ->orderByDesc('priority')
            ->orderBy('queue_number')
            ->first();

        if (! $ticket) {
            $ticket = QueueTicket::query()
                ->where('branch_id', $branchId)
                ->where('queue_date', $date)
                ->where('status', QueueStatus::Skipped)
                ->orderByDesc('priority')
                ->orderBy('queue_number')
                ->first();
        }

        if (! $ticket) {
            return null;
        }

        return $this->transition($ticket, QueueStatus::Called);
    }

    private function nextQueueNumber(int $branchId, string $queueDate): int
    {
        Branch::query()
            ->whereKey($branchId)
            ->lockForUpdate()
            ->value('id');

        $maxQueue = QueueTicket::query()
            ->where('branch_id', $branchId)
            ->where('queue_date', $queueDate)
            ->max('queue_number');

        return ((int) $maxQueue) + 1;
    }

    private function assertTransitionAllowed(QueueStatus $fromStatus, QueueStatus $toStatus): void
    {
        if ($fromStatus === $toStatus) {
            return;
        }

        $allowedTransitions = [
            QueueStatus::Waiting->value => [
                QueueStatus::Called->value,
                QueueStatus::Skipped->value,
                QueueStatus::Cancelled->value,
            ],
            QueueStatus::Called->value => [
                QueueStatus::CheckedIn->value,
                QueueStatus::InSession->value,
                QueueStatus::Skipped->value,
                QueueStatus::Cancelled->value,
            ],
            QueueStatus::CheckedIn->value => [
                QueueStatus::InSession->value,
                QueueStatus::Skipped->value,
                QueueStatus::Cancelled->value,
            ],
            QueueStatus::InSession->value => [
                QueueStatus::Finished->value,
                QueueStatus::Cancelled->value,
            ],
            QueueStatus::Finished->value => [],
            QueueStatus::Skipped->value => [
                QueueStatus::Called->value,
                QueueStatus::Cancelled->value,
            ],
            QueueStatus::Cancelled->value => [],
        ];

        if (! in_array($toStatus->value, $allowedTransitions[$fromStatus->value] ?? [], true)) {
            throw new RuntimeException(sprintf(
                'Perpindahan status antrean dari %s ke %s tidak diperbolehkan.',
                $fromStatus->value,
                $toStatus->value
            ));
        }
    }

    private function syncBookingStatus(QueueTicket $ticket, QueueStatus $toStatus): void
    {
        if (! $ticket->booking) {
            return;
        }

        $booking = $ticket->booking;

        if ($toStatus === QueueStatus::Finished) {
            $booking->status = BookingStatus::Done;
            $booking->save();

            return;
        }

        if ($toStatus === QueueStatus::InSession) {
            $booking->status = BookingStatus::InSession;
            $booking->save();

            return;
        }

        if (in_array($toStatus, [QueueStatus::Called, QueueStatus::CheckedIn], true)) {
            $booking->status = BookingStatus::CheckedIn;
            $booking->save();

            return;
        }

        if ($toStatus === QueueStatus::Cancelled && ($booking->status?->value ?? $booking->status) !== BookingStatus::Done->value) {
            $booking->status = BookingStatus::Cancelled;
            $booking->save();
        }
    }
}
