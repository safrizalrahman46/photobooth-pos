<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Http\Requests\AdminDeclineBookingRequest;
use App\Http\Requests\AdminConfirmBookingPaymentRequest;
use App\Http\Requests\AdminConfirmBookingRequest;
use App\Http\Requests\AdminStoreBookingRequest;
use App\Http\Requests\AdminUpdateBookingRequest;
use App\Models\Booking;
use App\Services\AdminBookingManagementService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

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

    public function decline(
        AdminDeclineBookingRequest $request,
        Booking $booking,
        AdminBookingManagementService $service,
    ): JsonResponse {
        $actorId = (int) ($request->user()?->id ?? 0);

        $service->decline(
            $booking,
            $actorId > 0 ? $actorId : null,
            (string) ($request->validated('reason') ?? ''),
        );

        return response()->json([
            'success' => true,
            'message' => 'Booking declined and cancelled successfully.',
        ]);
    }

    public function transferProof(Booking $booking): StreamedResponse
    {
        $rawPath = trim((string) ($booking->transfer_proof_path ?? ''));
        $normalizedPath = $this->normalizePublicDiskPath($rawPath);

        abort_if($normalizedPath === '', 404, 'Transfer proof not found.');
        abort_unless(Storage::disk('public')->exists($normalizedPath), 404, 'Transfer proof file is missing.');

        $fileName = basename($normalizedPath);

        return Storage::disk('public')->response($normalizedPath, $fileName, [
            'Content-Disposition' => 'inline; filename="'.$fileName.'"',
        ]);
    }

    private function normalizePublicDiskPath(string $path): string
    {
        $normalized = trim(str_replace('\\', '/', $path), '/');

        if (str_starts_with($normalized, 'public/')) {
            return trim(substr($normalized, 7), '/');
        }

        if (str_starts_with($normalized, 'storage/')) {
            return trim(substr($normalized, 8), '/');
        }

        return $normalized;
    }
}
