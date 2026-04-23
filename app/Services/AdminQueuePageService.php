<?php

namespace App\Services;

use App\Enums\BookingStatus;
use App\Enums\QueueStatus;
use App\Models\Booking;
use App\Models\QueueTicket;

class AdminQueuePageService
{
    public function payload(?string $queueDate = null): array
    {
        return [
            'queue_live' => $this->live($queueDate),
            'queue_booking_options' => $this->bookingOptions($queueDate),
        ];
    }

    public function live(?string $queueDate = null): array
    {
        $targetDate = $queueDate ?: now()->toDateString();

        $currentTicket = QueueTicket::query()
            ->with(['booking.package:id,name,duration_minutes'])
            ->whereDate('queue_date', $targetDate)
            ->where('status', QueueStatus::InSession->value)
            ->latest('started_at')
            ->first([
                'id',
                'booking_id',
                'branch_id',
                'queue_date',
                'source_type',
                'queue_code',
                'queue_number',
                'customer_name',
                'status',
                'called_at',
                'checked_in_at',
                'started_at',
            ]);

        if (! $currentTicket) {
            $currentTicket = QueueTicket::query()
                ->with(['booking.package:id,name,duration_minutes'])
                ->whereDate('queue_date', $targetDate)
                ->whereIn('status', [QueueStatus::Called->value, QueueStatus::CheckedIn->value])
                ->orderBy('queue_number')
                ->first([
                    'id',
                    'booking_id',
                    'branch_id',
                    'queue_date',
                    'source_type',
                    'queue_code',
                    'queue_number',
                    'customer_name',
                    'status',
                    'called_at',
                    'checked_in_at',
                    'started_at',
                ]);
        }

        $sessionDurationSeconds = max(
            60,
            ((int) ($currentTicket?->booking?->package?->duration_minutes ?? 20)) * 60,
        );

        $startedAt = $currentTicket?->started_at
            ?? $currentTicket?->checked_in_at
            ?? $currentTicket?->called_at;

        $elapsedSeconds = $startedAt ? (int) $startedAt->diffInSeconds(now()) : 0;
        $remainingSeconds = max($sessionDurationSeconds - $elapsedSeconds, 0);
        $progressPercentage = $sessionDurationSeconds > 0
            ? min(100, round(($elapsedSeconds / $sessionDurationSeconds) * 100, 2))
            : 0;

        $waitingTickets = QueueTicket::query()
            ->with(['booking.package:id,name'])
            ->whereDate('queue_date', $targetDate)
            ->whereIn('status', [
                QueueStatus::Waiting->value,
                QueueStatus::Called->value,
                QueueStatus::CheckedIn->value,
            ])
            ->orderBy('queue_number')
            ->limit(8)
            ->get([
                'id',
                'booking_id',
                'branch_id',
                'queue_date',
                'source_type',
                'queue_code',
                'queue_number',
                'customer_name',
                'status',
                'created_at',
            ])
            ->map(function (QueueTicket $ticket) use ($targetDate): array {
                $status = (string) $ticket->status->value;

                return [
                    'ticket_id' => (int) $ticket->id,
                    'booking_id' => $ticket->booking_id ? (int) $ticket->booking_id : null,
                    'branch_id' => (int) $ticket->branch_id,
                    'queue_date' => $ticket->queue_date?->toDateString() ?? $targetDate,
                    'source_type' => (string) ($ticket->source_type?->value ?? $ticket->source_type),
                    'queue_code' => (string) $ticket->queue_code,
                    'queue_number' => (int) $ticket->queue_number,
                    'customer_name' => (string) $ticket->customer_name,
                    'package_name' => (string) ($ticket->booking?->package?->name ?? '-'),
                    'status' => $status,
                    'status_label' => $this->statusLabel($status),
                    'next_status' => $this->nextQueueStatus($status),
                    'previous_status' => $this->previousQueueStatus($status),
                    'can_cancel' => true,
                    'added_at' => $ticket->created_at?->format('H:i') ?? '-',
                ];
            })
            ->values()
            ->all();

        $queueStats = [
            'in_queue' => QueueTicket::query()
                ->whereDate('queue_date', $targetDate)
                ->whereIn('status', [
                    QueueStatus::Waiting->value,
                    QueueStatus::Called->value,
                    QueueStatus::CheckedIn->value,
                    QueueStatus::InSession->value,
                ])
                ->count(),
            'in_session' => QueueTicket::query()
                ->whereDate('queue_date', $targetDate)
                ->where('status', QueueStatus::InSession->value)
                ->count(),
            'waiting' => QueueTicket::query()
                ->whereDate('queue_date', $targetDate)
                ->where('status', QueueStatus::Waiting->value)
                ->count(),
            'completed_today' => QueueTicket::query()
                ->whereDate('queue_date', $targetDate)
                ->where('status', QueueStatus::Finished->value)
                ->count(),
        ];

        return [
            'stats' => $queueStats,
            'current' => $currentTicket ? [
                'ticket_id' => (int) $currentTicket->id,
                'booking_id' => $currentTicket->booking_id ? (int) $currentTicket->booking_id : null,
                'branch_id' => (int) $currentTicket->branch_id,
                'queue_date' => $currentTicket->queue_date?->toDateString() ?? $targetDate,
                'source_type' => (string) ($currentTicket->source_type?->value ?? $currentTicket->source_type),
                'queue_code' => (string) $currentTicket->queue_code,
                'queue_number' => (int) $currentTicket->queue_number,
                'customer_name' => (string) $currentTicket->customer_name,
                'status' => (string) $currentTicket->status->value,
                'status_label' => $this->statusLabel((string) $currentTicket->status->value),
                'package_name' => (string) ($currentTicket->booking?->package?->name ?? '-'),
                'session_duration_seconds' => (int) $sessionDurationSeconds,
                'elapsed_seconds' => (int) $elapsedSeconds,
                'remaining_seconds' => (int) $remainingSeconds,
                'progress_percentage' => (float) $progressPercentage,
                'can_complete' => in_array((string) $currentTicket->status->value, [
                    QueueStatus::Called->value,
                    QueueStatus::CheckedIn->value,
                    QueueStatus::InSession->value,
                ], true),
                'can_skip' => in_array((string) $currentTicket->status->value, [
                    QueueStatus::Waiting->value,
                    QueueStatus::Called->value,
                    QueueStatus::CheckedIn->value,
                    QueueStatus::InSession->value,
                ], true),
            ] : null,
            'waiting' => $waitingTickets,
        ];
    }

    public function bookingOptions(?string $queueDate = null): array
    {
        $targetDate = $queueDate ?: now()->toDateString();

        return Booking::query()
            ->with(['branch:id,name', 'package:id,name'])
            ->whereDate('booking_date', $targetDate)
            ->whereIn('status', [
                BookingStatus::Confirmed->value,
                BookingStatus::Paid->value,
                BookingStatus::CheckedIn->value,
            ])
            ->whereDoesntHave('queueTicket')
            ->orderBy('start_at')
            ->orderBy('id')
            ->limit(100)
            ->get([
                'id',
                'booking_code',
                'branch_id',
                'package_id',
                'customer_name',
                'booking_date',
                'start_at',
                'status',
            ])
            ->map(function (Booking $booking): array {
                $dateText = $booking->booking_date?->format('d M Y') ?? '-';
                $timeText = $booking->start_at?->format('H:i') ?? '--:--';

                return [
                    'id' => (int) $booking->id,
                    'booking_code' => (string) ($booking->booking_code ?? ('BK-' . $booking->id)),
                    'branch_id' => (int) $booking->branch_id,
                    'branch_name' => (string) ($booking->branch?->name ?? '-'),
                    'package_id' => $booking->package_id ? (int) $booking->package_id : null,
                    'package_name' => (string) ($booking->package?->name ?? '-'),
                    'customer_name' => (string) $booking->customer_name,
                    'booking_date' => $booking->booking_date?->toDateString(),
                    'start_time' => $booking->start_at?->format('H:i:s'),
                    'status' => (string) ($booking->status?->value ?? $booking->status),
                    'status_label' => $this->statusLabel((string) ($booking->status?->value ?? $booking->status)),
                    'display_text' => sprintf(
                        '%s - %s (%s, %s %s)',
                        (string) ($booking->booking_code ?? ('BK-' . $booking->id)),
                        (string) $booking->customer_name,
                        (string) ($booking->branch?->name ?? '-'),
                        $dateText,
                        $timeText,
                    ),
                ];
            })
            ->values()
            ->all();
    }

    private function nextQueueStatus(string $status): ?string
    {
        return match ($status) {
            QueueStatus::Waiting->value => QueueStatus::Called->value,
            QueueStatus::Called->value => QueueStatus::CheckedIn->value,
            QueueStatus::CheckedIn->value => QueueStatus::InSession->value,
            QueueStatus::InSession->value => QueueStatus::Finished->value,
            default => null,
        };
    }

    private function previousQueueStatus(string $status): ?string
    {
        return match ($status) {
            QueueStatus::Called->value => QueueStatus::Waiting->value,
            QueueStatus::CheckedIn->value => QueueStatus::Called->value,
            QueueStatus::InSession->value => QueueStatus::CheckedIn->value,
            default => null,
        };
    }

    private function statusLabel(string $status): string
    {
        return ucwords(str_replace('_', ' ', $status));
    }
}
