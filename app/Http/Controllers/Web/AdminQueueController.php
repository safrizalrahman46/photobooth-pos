<?php

namespace App\Http\Controllers\Web;

use App\Enums\QueueStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\QueueCallNextRequest;
use App\Http\Requests\QueueCheckInRequest;
use App\Http\Requests\QueueTransitionRequest;
use App\Http\Requests\QueueWalkInRequest;
use App\Models\QueueTicket;
use App\Services\AdminQueuePageService;
use App\Services\QueueService;
use Illuminate\Http\JsonResponse;

class AdminQueueController extends Controller
{
    public function index(AdminQueuePageService $service): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => $service->payload(),
        ]);
    }

    public function callNext(QueueCallNextRequest $request, QueueService $queueService, AdminQueuePageService $service): JsonResponse
    {
        $payload = $request->validated();
        $queueDate = (string) ($payload['queue_date'] ?? now()->toDateString());

        $queueService->callNextForBranch((int) $payload['branch_id'], $payload['queue_date'] ?? null);

        return response()->json([
            'success' => true,
            'message' => 'Queue updated successfully.',
            'data' => $service->payload($queueDate),
        ]);
    }

    public function checkIn(QueueCheckInRequest $request, QueueService $queueService, AdminQueuePageService $service): JsonResponse
    {
        $ticket = $queueService->checkInByBookingId((int) $request->validated('booking_id'));

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
        $queueDate = (string) ($payload['queue_date'] ?? now()->toDateString());

        $queueService->createWalkInFromPayload($payload);

        return response()->json([
            'success' => true,
            'message' => 'Queue ticket created successfully.',
            'data' => $service->payload($queueDate),
        ], 201);
    }
}