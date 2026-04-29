<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Services\BookingPaymentService;
use App\Support\ApiResponder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use RuntimeException;

class MidtransWebhookController extends Controller
{
    public function __construct(
        private readonly BookingPaymentService $bookingPaymentService,
        private readonly ApiResponder $responder,
    ) {}

    public function __invoke(Request $request): JsonResponse
    {
        try {
            $booking = $this->bookingPaymentService->handleNotification($request->all());

            return $this->responder->success([
                'booking_id' => $booking?->id,
                'booking_code' => $booking?->booking_code,
                'status' => $booking?->status?->value ?? $booking?->status,
            ], 'Notifikasi Midtrans berhasil diproses.');
        } catch (RuntimeException $exception) {
            return $this->responder->error($exception->getMessage(), 422);
        }
    }
}
