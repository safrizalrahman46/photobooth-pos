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
<<<<<<< HEAD
use Illuminate\Http\Request;
use RuntimeException;
=======
>>>>>>> fc7ace865dfae888f032ba57ff5855d596c41b93

class QueueController extends Controller
{
    public function __construct(
        private readonly QueueService $queueService,
        private readonly QueueReadService $queueReadService,
        private readonly ApiResponder $responder,
    ) {}

    public function index(QueueIndexRequest $request): JsonResponse
    {
<<<<<<< HEAD
        abort_unless($request->user()?->can('queue.view'), 403);

        $perPage = min((int) $request->integer('per_page', 15), 100);
=======
        $payload = $request->validated();
        $perPage = (int) ($payload['per_page'] ?? 15);
>>>>>>> fc7ace865dfae888f032ba57ff5855d596c41b93

        $tickets = $this->queueReadService->paginate($payload, $perPage);

        return $this->responder->paginated($tickets, QueueTicketResource::collection($tickets), 'Daftar antrean berhasil dimuat.');
    }

    public function checkIn(QueueCheckInRequest $request): JsonResponse
    {
<<<<<<< HEAD
        abort_unless($request->user()?->can('queue.manage'), 403);

        $booking = Booking::query()
            ->whereKey($request->integer('booking_id'))
            ->with('queueTicket')
            ->firstOrFail();

        if ($booking->queueTicket) {
            return $this->responder->error('Booking ini sudah memiliki tiket antrean.', 422);
        }

        try {
            $ticket = $this->queueService->checkInBooking($booking);
        } catch (RuntimeException $exception) {
            return $this->responder->error($exception->getMessage(), 422);
        }
=======
        $ticket = $this->queueService->checkInByBookingId((int) $request->validated('booking_id'));
>>>>>>> fc7ace865dfae888f032ba57ff5855d596c41b93

        return $this->responder->success(new QueueTicketResource($ticket->load('booking')), 'Check-in booking berhasil.', 201);
    }

    public function walkIn(QueueWalkInRequest $request): JsonResponse
    {
        abort_unless($request->user()?->can('queue.manage'), 403);

        try {
            $ticket = $this->queueService->createWalkIn($request->validated());
        } catch (RuntimeException $exception) {
            return $this->responder->error($exception->getMessage(), 422);
        }

        return $this->responder->success(new QueueTicketResource($ticket), 'Antrean walk-in berhasil dibuat.', 201);
    }

    public function transition(QueueTransitionRequest $request, QueueTicket $queueTicket): JsonResponse
    {
        abort_unless($request->user()?->can('queue.manage'), 403);

        $status = QueueStatus::from($request->validated('status'));

        try {
            $ticket = $this->queueService->transition($queueTicket, $status);
        } catch (RuntimeException $exception) {
            return $this->responder->error($exception->getMessage(), 422);
        }

        return $this->responder->success(new QueueTicketResource($ticket->load('booking')), 'Status antrean berhasil diperbarui.');
    }

    public function callNext(QueueCallNextRequest $request): JsonResponse
    {
<<<<<<< HEAD
        abort_unless($request->user()?->can('queue.manage'), 403);

        $payload = $request->validate([
            'branch_id' => ['required', 'integer', 'exists:branches,id'],
            'queue_date' => ['nullable', 'date_format:Y-m-d'],
        ]);

        $date = $payload['queue_date'] ?? now()->toDateString();

        try {
            $ticket = $this->queueService->callNext((int) $payload['branch_id'], $date);
        } catch (RuntimeException $exception) {
            return $this->responder->error($exception->getMessage(), 422);
        }
=======
        $payload = $request->validated();

        $ticket = $this->queueService->callNextForBranch((int) $payload['branch_id'], $payload['queue_date'] ?? null);
>>>>>>> fc7ace865dfae888f032ba57ff5855d596c41b93

        if (! $ticket) {
            return $this->responder->success(null, 'Tidak ada antrean menunggu saat ini.');
        }

        return $this->responder->success(new QueueTicketResource($ticket->load('booking')), 'Antrean berikutnya berhasil dipanggil.');
    }
}
