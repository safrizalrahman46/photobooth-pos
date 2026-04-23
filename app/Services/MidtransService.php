<?php

namespace App\Services;

use App\Models\Booking;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class MidtransService
{
    public function enabled(): bool
    {
        return (bool) config('services.midtrans.enabled') && filled(config('services.midtrans.server_key'));
    }

    public function createBookingTransaction(Booking $booking, float $chargeAmount): array
    {
        if (! $this->enabled()) {
            throw new RuntimeException('Midtrans belum dikonfigurasi.');
        }

        $normalizedChargeAmount = max((float) round($chargeAmount, 2), 0);

        if ($normalizedChargeAmount <= 0) {
            throw new RuntimeException('Nominal pembayaran tidak valid.');
        }

        $orderId = $booking->booking_code;
        $expiryMinutes = (int) config('services.midtrans.expiry_minutes', 15);
        $notificationUrl = url('/api/v1/payments/midtrans/notifications');

        $response = Http::withBasicAuth((string) config('services.midtrans.server_key'), '')
            ->withHeaders([
                'X-Override-Notification' => $notificationUrl,
            ])
            ->acceptJson()
            ->post(rtrim((string) config('services.midtrans.base_url'), '/').'/snap/v1/transactions', [
                'transaction_details' => [
                    'order_id' => $orderId,
                    'gross_amount' => (int) round($normalizedChargeAmount),
                ],
                'customer_details' => [
                    'first_name' => $booking->customer_name,
                    'email' => $booking->customer_email,
                    'phone' => $booking->customer_phone,
                ],
                'item_details' => [[
                    'id' => 'booking-'.$booking->package_id,
                    'price' => (int) round($normalizedChargeAmount),
                    'quantity' => 1,
                    'name' => $this->resolveItemName($booking),
                ]],
                'expiry' => [
                    'unit' => 'minute',
                    'duration' => $expiryMinutes,
                ],
                'callbacks' => [
                    'finish' => route('booking.success', $booking->booking_code),
                    'unfinish' => route('booking.success', $booking->booking_code),
                    'error' => route('booking.success', $booking->booking_code),
                ],
            ]);

        if ($response->failed()) {
            throw new RuntimeException('Gagal membuat transaksi Midtrans.');
        }

        $payload = $response->json();

        if (! is_array($payload) || blank($payload['redirect_url'] ?? null)) {
            throw new RuntimeException('Respons Midtrans tidak valid.');
        }

        return [
            'order_id' => $orderId,
            'token' => $payload['token'] ?? null,
            'redirect_url' => $payload['redirect_url'],
            'payload' => $payload,
            'expires_at' => now()->addMinutes($expiryMinutes),
        ];
    }

    private function resolveItemName(Booking $booking): string
    {
        $baseName = $booking->package?->name ?? 'Booking Photobooth';
        $paymentType = (string) ($booking->payment_type ?? 'full');

        return $paymentType === 'dp50'
            ? $baseName.' - DP 50%'
            : $baseName.' - Pelunasan';
    }

    public function verifyNotificationSignature(array $payload): bool
    {
        $signature = (string) ($payload['signature_key'] ?? '');
        $orderId = (string) ($payload['order_id'] ?? '');
        $statusCode = (string) ($payload['status_code'] ?? '');
        $grossAmount = (string) ($payload['gross_amount'] ?? '');
        $serverKey = (string) config('services.midtrans.server_key');

        if ($signature === '' || $orderId === '' || $statusCode === '' || $grossAmount === '' || $serverKey === '') {
            return false;
        }

        $expected = hash('sha512', $orderId.$statusCode.$grossAmount.$serverKey);

        return hash_equals($expected, $signature);
    }
}
