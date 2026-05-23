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
        private readonly ActivityLogger $activityLogger,
    ) {}

    public function checkInBooking(Booking $booking): QueueTicket
    {
        return DB::transaction(function () use ($booking): QueueTicket {
            $date = Carbon::parse($booking->booking_date);

            if ($date->toDateString() !== $this->queueTodayDate()) {
                throw new RuntimeException('Check-in booking hanya dapat dilakukan pada hari yang sama.');
            }

            if (in_array($booking->status?->value ?? $booking->status, [
                BookingStatus::Cancelled->value,
                BookingStatus::Done->value,
            ], true)) {
                throw new RuntimeException('Status booking tidak dapat dimasukkan ke antrean.');
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
                'priority' => 0,
            ]);

            $booking->status = BookingStatus::InQueue;
            $booking->save();

            $this->activityLogger->log(
                'queue',
                'booking_checked_in',
                null,
                QueueTicket::class,
                (int) $ticket->id,
                [
                    'message' => sprintf(
                        'Booking %s masuk antrean %s.',
                        (string) ($booking->booking_code ?? ('BK-'.$booking->id)),
                        (string) $ticket->queue_code,
                    ),
                    'label' => (string) $ticket->queue_code,
                    'booking_code' => (string) ($booking->booking_code ?? ('BK-'.$booking->id)),
                    'customer_name' => (string) $ticket->customer_name,
                    'branch_id' => (int) $ticket->branch_id,
                    'status' => QueueStatus::Waiting->value,
                    'source_type' => QueueSourceType::Booking->value,
                ],
            );

            return $ticket;
        });
    }

    public function createWalkIn(array $payload): QueueTicket
    {
        return DB::transaction(function () use ($payload): QueueTicket {
            $date = Carbon::parse($payload['queue_date']);
            $queueNumber = $this->nextQueueNumber((int) $payload['branch_id'], $date->toDateString());

            $ticket = QueueTicket::query()->create([
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

            $this->activityLogger->log(
                'queue',
                'walk_in_created',
                null,
                QueueTicket::class,
                (int) $ticket->id,
                [
                    'message' => sprintf('Walk-in %s ditambahkan ke antrean.', (string) $ticket->queue_code),
                    'label' => (string) $ticket->queue_code,
                    'customer_name' => (string) $ticket->customer_name,
                    'branch_id' => (int) $ticket->branch_id,
                    'queue_date' => $ticket->queue_date?->toDateString(),
                    'status' => QueueStatus::Waiting->value,
                    'source_type' => QueueSourceType::WalkIn->value,
                ],
            );

            return $ticket;
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

        $this->activityLogger->log(
            'queue',
            'status_changed',
            null,
            QueueTicket::class,
            (int) $ticket->id,
            [
                'message' => sprintf(
                    'Status antrean %s berubah dari %s ke %s.',
                    (string) $ticket->queue_code,
                    $fromStatus->value,
                    $toStatus->value,
                ),
                'label' => (string) $ticket->queue_code,
                'customer_name' => (string) $ticket->customer_name,
                'branch_id' => (int) $ticket->branch_id,
                'from_status' => $fromStatus->value,
                'to_status' => $toStatus->value,
            ],
        );

        return $ticket->refresh();
    }

    public function callNext(int $branchId, string $date): ?QueueTicket
    {
        $hasActiveTicket = QueueTicket::query()
            ->where('branch_id', $branchId)
            ->where('queue_date', $date)
            ->whereIn('status', [
                QueueStatus::Called->value,
                QueueStatus::CheckedIn->value,
                QueueStatus::InSession->value,
            ])
            ->exists();

        if ($hasActiveTicket) {
            throw new RuntimeException('Selesaikan atau lewati antrean aktif sebelum memanggil antrean berikutnya.');
        }

        $ticket = QueueTicket::query()
            ->where('branch_id', $branchId)
            ->where('queue_date', $date)
            ->where('status', QueueStatus::Waiting)
            ->orderBy('queue_number')
            ->first();

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

        $currentStatus = $booking->status instanceof BookingStatus
            ? $booking->status
            : BookingStatus::from((string) $booking->status);

        $nextStatus = match ($toStatus) {
            QueueStatus::Finished => BookingStatus::Done,
            QueueStatus::InSession => BookingStatus::InSession,
            QueueStatus::Called, QueueStatus::CheckedIn => BookingStatus::CheckedIn,
            QueueStatus::Cancelled => $currentStatus !== BookingStatus::Done ? BookingStatus::Cancelled : null,
            default => null,
        };

        if (! $nextStatus || $nextStatus === $currentStatus) {
            return;
        }

        $booking->status = $nextStatus;
        $booking->save();

        $this->activityLogger->log(
            'bookings',
            'status_changed',
            null,
            Booking::class,
            (int) $booking->id,
            [
                'message' => sprintf(
                    'Status booking %s berubah dari %s ke %s melalui antrean.',
                    (string) ($booking->booking_code ?? ('BK-'.$booking->id)),
                    $currentStatus->value,
                    $nextStatus->value,
                ),
                'label' => (string) ($booking->booking_code ?? ('BK-'.$booking->id)),
                'from_status' => $currentStatus->value,
                'to_status' => $nextStatus->value,
                'queue_code' => (string) $ticket->queue_code,
            ],
        );
    }

    private function queueTodayDate(): string
    {
        return now(config('app.queue_timezone', 'Asia/Jakarta'))->toDateString();
    }
}
