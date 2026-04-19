<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Http\Requests\AdminConfirmBookingPaymentRequest;
use App\Http\Requests\AdminConfirmBookingRequest;
use App\Http\Requests\AdminStoreBookingRequest;
use App\Http\Requests\AdminUpdateBookingRequest;
use App\Models\Booking;
use App\Services\AdminBookingManagementService;
use Illuminate\Http\JsonResponse;

class AdminBookingController extends Controller
{
    public function store(AdminStoreBookingRequest $request, AdminBookingManagementService $service): JsonResponse
    {
        $booking = $service->create($request->validated());

        return response()->json([
            'success' => true,
            'message' => 'Booking created successfully.',
            'data' => [
                'booking_id' => (int) $booking->id,
            ],
        ], 201);
    }

    public function update(AdminUpdateBookingRequest $request, Booking $booking, AdminBookingManagementService $service): JsonResponse
    {
        $service->update($booking, $request->validated());

        return response()->json([
            'success' => true,
            'message' => 'Booking updated successfully.',
        ]);
    }

    public function destroy(Booking $booking, AdminBookingManagementService $service): JsonResponse
    {
        $service->delete($booking);

        return response()->json([
            'success' => true,
            'message' => 'Booking deleted successfully.',
        ]);
    }

    public function confirm(
        AdminConfirmBookingRequest $request,
        Booking $booking,
        AdminBookingManagementService $service,
    ): JsonResponse
    {
        $actorId = (int) ($request->user()?->id ?? 0);

        $service->confirm(
            $booking,
            $actorId > 0 ? $actorId : null,
            (string) ($request->validated('reason') ?? ''),
        );

        return response()->json([
            'success' => true,
            'message' => 'Booking confirmed successfully.',
        ]);
    }

    public function confirmPayment(
        AdminConfirmBookingPaymentRequest $request,
        Booking $booking,
        AdminBookingManagementService $service,
    ): JsonResponse {
        $cashierId = (int) ($request->user()?->id ?? 0);

        $updatedTransaction = $service->confirmPayment($booking, $request->validated(), $cashierId);

        return response()->json([
            'success' => true,
            'message' => 'Payment confirmed successfully.',
            'data' => [
                'transaction_id' => (int) $updatedTransaction->id,
                'transaction_status' => (string) $updatedTransaction->status->value,
                'paid_amount' => (float) $updatedTransaction->paid_amount,
                'total_amount' => (float) $updatedTransaction->total_amount,
            ],
        ]);
    }
}
