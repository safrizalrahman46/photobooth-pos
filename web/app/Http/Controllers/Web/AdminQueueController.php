<?php

namespace App\Http\Controllers\Web;

use App\Enums\QueueStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\QueueCallNextRequest;
use App\Http\Requests\QueueCheckInRequest;
use App\Http\Requests\QueueTransitionRequest;
use App\Http\Requests\QueueWalkInRequest;
use App\Models\Booking;
use App\Models\QueueTicket;
use App\Services\AdminQueuePageService;
use App\Services\QueueService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use RuntimeException;

class AdminQueueController extends Controller
{
    public function index(Request $request, AdminQueuePageService $service): JsonResponse
    {
        $payload = $request->validate([
            'queue_date' => ['nullable', 'date_format:Y-m-d'],
            'branch_id' => ['nullable', 'integer', 'exists:branches,id'],
        ]);

        $queueDate = isset($payload['queue_date']) ? (string) $payload['queue_date'] : null;
        $branchId = isset($payload['branch_id']) ? (int) $payload['branch_id'] : null;

        return response()->json([
            'success' => true,
            'data' => $service->payload($queueDate, $branchId),
        ]);
    }

    public function callNext(QueueCallNextRequest $request, QueueService $queueService, AdminQueuePageService $service): JsonResponse
    {
        $payload = $request->validated();
        $queueDate = (string) ($payload['queue_date'] ?? now(config('app.queue_timezone', 'Asia/Jakarta'))->toDateString());

        try {
            $queueService->callNext((int) $payload['branch_id'], $queueDate);
        } catch (RuntimeException $exception) {
            return response()->json([
                'success' => false,
                'message' => $exception->getMessage(),
            ], 422);
        }

        return response()->json([
            'success' => true,
            'message' => 'Queue updated successfully.',
            'data' => $service->payload($queueDate),
        ]);
    }

    public function checkIn(QueueCheckInRequest $request, QueueService $queueService, AdminQueuePageService $service): JsonResponse
    {
        $booking = Booking::query()
            ->whereKey((int) $request->validated('booking_id'))
            ->with('queueTicket')
            ->firstOrFail();

        if ($booking->queueTicket) {
            return response()->json([
                'success' => false,
                'message' => 'Booking ini sudah memiliki tiket antrean.',
            ], 422);
        }

        try {
            $ticket = $queueService->checkInBooking($booking);
        } catch (RuntimeException $exception) {
            return response()->json([
                'success' => false,
                'message' => $exception->getMessage(),
            ], 422);
        }

        return response()->json([
            'success' => true,
            'message' => 'Booking berhasil ditambahkan ke antrean.',
            'data' => $service->payload($ticket->queue_date?->toDateString()),
        ], 201);
    }

    public function transition(
        QueueTransitionRequest $request,
        QueueTicket $queueTicket,
        QueueService $queueService,
        AdminQueuePageService $service,
    ): JsonResponse
    {
        $payload = $request->validated();

        $ticket = $queueService->transition($queueTicket, QueueStatus::from((string) $payload['status']));

        return response()->json([
            'success' => true,
            'message' => 'Queue status updated successfully.',
            'data' => $service->payload($ticket->queue_date?->toDateString()),
        ]);
    }

    public function walkIn(QueueWalkInRequest $request, QueueService $queueService, AdminQueuePageService $service): JsonResponse
    {
        $payload = $request->validated();
        $queueDate = (string) ($payload['queue_date'] ?? now(config('app.queue_timezone', 'Asia/Jakarta'))->toDateString());

        try {
            $queueService->createWalkIn($payload);
        } catch (RuntimeException $exception) {
            return response()->json([
                'success' => false,
                'message' => $exception->getMessage(),
            ], 422);
        }

        return response()->json([
            'success' => true,
            'message' => 'Queue ticket created successfully.',
            'data' => $service->payload($queueDate),
        ], 201);
    }
}
