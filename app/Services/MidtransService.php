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

    public function createBookingTransaction(Booking $booking, float $grossAmount): array
    {
        if (! $this->enabled()) {
            throw new RuntimeException('Midtrans belum dikonfigurasi.');
        }

        $orderId = $booking->booking_code;
        $chargeAmount = max((int) round($grossAmount), 0);
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
                    'gross_amount' => $chargeAmount,
                ],
                'customer_details' => [
                    'first_name' => $booking->customer_name,
                    'email' => $booking->customer_email,
                    'phone' => $booking->customer_phone,
                ],
                'item_details' => [[
                    'id' => 'booking-'.$booking->package_id,
                    'price' => $chargeAmount,
                    'quantity' => 1,
                    'name' => ($booking->payment_type === 'dp50' ? 'DP 50% - ' : 'Full Lunas - ').($booking->package?->name ?? 'Booking Photobooth'),
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
