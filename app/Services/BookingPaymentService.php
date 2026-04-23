<?php

namespace App\Services;

use App\Enums\BookingStatus;
use App\Jobs\SendBookingNotificationJob;
use App\Models\Booking;
use App\Models\BookingStatusLog;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class BookingPaymentService
{
    public function __construct(
        private readonly MidtransService $midtransService,
    ) {}

    public function gatewayEnabled(): bool
    {
        return $this->midtransService->enabled();
    }

    public function startOnlinePayment(Booking $booking): Booking
    {
        $paymentType = (string) ($booking->payment_type ?? 'full');

        if (! in_array($paymentType, ['full', 'dp50'], true)) {
            throw new RuntimeException('Booking ini tidak menggunakan pembayaran online.');
        }

        $booking->loadMissing('package');

        $chargeAmount = $this->resolveChargeAmount($booking);
        $session = $this->midtransService->createBookingTransaction($booking, $chargeAmount);

        $booking->forceFill([
            'payment_gateway' => 'midtrans',
            'payment_reference' => $session['order_id'],
            'payment_token' => $session['token'],
            'payment_url' => $session['redirect_url'],
            'payment_payload' => $session['payload'],
            'payment_expires_at' => $session['expires_at'],
        ])->save();

        return $booking->refresh();
    }

    public function handleNotification(array $payload): ?Booking
    {
        if (! $this->midtransService->verifyNotificationSignature($payload)) {
            throw new RuntimeException('Signature Midtrans tidak valid.');
        }

        $orderId = (string) ($payload['order_id'] ?? '');

        if ($orderId === '') {
            return null;
        }

        $booking = Booking::query()
            ->where('payment_reference', $orderId)
            ->orWhere('booking_code', $orderId)
            ->first();

        if (! $booking) {
            return null;
        }

        return DB::transaction(function () use ($booking, $payload): Booking {
            $booking->payment_gateway = 'midtrans';
            $booking->payment_reference = (string) ($payload['order_id'] ?? $booking->payment_reference);
            $booking->payment_payload = $payload;

            $transactionStatus = (string) ($payload['transaction_status'] ?? '');
            $fraudStatus = (string) ($payload['fraud_status'] ?? '');

            if ($this->isPaidStatus($transactionStatus, $fraudStatus)) {
                $settledAmount = $this->resolveChargeAmount($booking);
                $totalAmount = (float) $booking->total_amount;
                $existingPaidAmount = (float) $booking->paid_amount;
                $newPaidAmount = min(max($existingPaidAmount, $settledAmount), $totalAmount);

                $booking->paid_amount = $newPaidAmount;
                $booking->deposit_amount = max((float) $booking->deposit_amount, $settledAmount);
                $booking->paid_at = $booking->paid_at ?? now();
                $booking->save();

                if ($newPaidAmount >= $totalAmount || (string) ($booking->payment_type ?? 'full') === 'full') {
                    $booking->paid_amount = $totalAmount;
                    $booking->deposit_amount = $totalAmount;
                    $booking->save();
                    $this->transitionStatus($booking, BookingStatus::Paid, 'Pembayaran QR berhasil diterima.');
                } else {
                    $this->transitionStatus($booking, BookingStatus::Confirmed, 'DP 50% QR berhasil diterima.');
                }

                return $booking->refresh();
            }

            if ($this->isCancelledStatus($transactionStatus) && (float) $booking->paid_amount <= 0) {
                $booking->save();
                $this->transitionStatus($booking, BookingStatus::Cancelled, 'Pembayaran Midtrans dibatalkan atau kedaluwarsa.');

                return $booking->refresh();
            }

            $booking->save();

            return $booking->refresh();
        });
    }

    private function isPaidStatus(string $transactionStatus, string $fraudStatus): bool
    {
        if ($transactionStatus === 'settlement') {
            return true;
        }

        return $transactionStatus === 'capture' && ($fraudStatus === '' || $fraudStatus === 'accept');
    }

    private function isCancelledStatus(string $transactionStatus): bool
    {
        return in_array($transactionStatus, ['cancel', 'deny', 'expire'], true);
    }

    private function transitionStatus(Booking $booking, BookingStatus $status, string $reason): void
    {
        $currentStatus = $booking->status instanceof BookingStatus
            ? $booking->status
            : BookingStatus::from((string) $booking->status);

        if ($currentStatus === $status) {
            return;
        }

        $booking->status = $status;
        $booking->save();

        BookingStatusLog::query()->create([
            'booking_id' => $booking->id,
            'from_status' => $currentStatus->value,
            'to_status' => $status->value,
            'reason' => $reason,
        ]);

        SendBookingNotificationJob::dispatch($booking->id, 'status_changed');
    }

    private function resolveChargeAmount(Booking $booking): float
    {
        $totalAmount = max((float) $booking->total_amount, 0);
        $paymentType = (string) ($booking->payment_type ?? 'full');

        if ($paymentType === 'dp50') {
            return round($totalAmount * 0.5, 2);
        }

        return $totalAmount;
    }
}
