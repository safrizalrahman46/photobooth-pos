<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\QueueStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\QueueCallNextRequest;
use App\Http\Requests\QueueCheckInRequest;
use App\Http\Requests\QueueIndexRequest;
use App\Http\Requests\QueueTransitionRequest;
use App\Http\Requests\QueueWalkInRequest;
use App\Http\Resources\QueueTicketResource;
use App\Models\QueueTicket;
use App\Services\QueueReadService;
use App\Services\QueueService;
use App\Support\ApiResponder;
use Illuminate\Http\JsonResponse;

class QueueController extends Controller
{
    public function __construct(
        private readonly QueueService $queueService,
        private readonly QueueReadService $queueReadService,
        private readonly ApiResponder $responder,
    ) {}

    public function index(QueueIndexRequest $request): JsonResponse
    {
        $payload = $request->validated();
        $perPage = (int) ($payload['per_page'] ?? 15);

        $tickets = $this->queueReadService->paginate($payload, $perPage);

        return $this->responder->paginated($tickets, QueueTicketResource::collection($tickets), 'Daftar antrean berhasil dimuat.');
    }

    public function checkIn(QueueCheckInRequest $request): JsonResponse
    {
        $ticket = $this->queueService->checkInByBookingId((int) $request->validated('booking_id'));

        return $this->responder->success(new QueueTicketResource($ticket->load('booking')), 'Check-in booking berhasil.', 201);
    }

    public function walkIn(QueueWalkInRequest $request): JsonResponse
    {
        $ticket = $this->queueService->createWalkIn($request->validated());

        return $this->responder->success(new QueueTicketResource($ticket), 'Antrean walk-in berhasil dibuat.', 201);
    }

    public function transition(QueueTransitionRequest $request, QueueTicket $queueTicket): JsonResponse
    {
        $status = QueueStatus::from($request->validated('status'));
        $ticket = $this->queueService->transition($queueTicket, $status);

        return $this->responder->success(new QueueTicketResource($ticket->load('booking')), 'Status antrean berhasil diperbarui.');
    }

    public function callNext(QueueCallNextRequest $request): JsonResponse
    {
        $payload = $request->validated();

        $ticket = $this->queueService->callNextForBranch((int) $payload['branch_id'], $payload['queue_date'] ?? null);

        if (! $ticket) {
            return $this->responder->success(null, 'Tidak ada antrean menunggu saat ini.');
        }

        return $this->responder->success(new QueueTicketResource($ticket->load('booking')), 'Antrean berikutnya berhasil dipanggil.');
    }
}
