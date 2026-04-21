<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\QueueStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\QueueCheckInRequest;
use App\Http\Requests\QueueTransitionRequest;
use App\Http\Requests\QueueWalkInRequest;
use App\Http\Resources\QueueTicketResource;
use App\Models\Booking;
use App\Models\QueueTicket;
use App\Services\QueueService;
use App\Support\ApiResponder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use RuntimeException;

class QueueController extends Controller
{
    public function __construct(
        private readonly QueueService $queueService,
        private readonly ApiResponder $responder,
    ) {}

    public function index(Request $request): JsonResponse
    {
        abort_unless($request->user()?->can('queue.view'), 403);

        $perPage = min((int) $request->integer('per_page', 15), 100);

        $query = QueueTicket::query()
            ->with(['booking'])
            ->orderByDesc('queue_date')
            ->orderBy('queue_number');

        if ($request->filled('branch_id')) {
            $query->where('branch_id', $request->integer('branch_id'));
        }

        if ($request->filled('queue_date')) {
            $query->whereDate('queue_date', $request->string('queue_date')->toString());
        }

        if ($request->filled('status')) {
            $query->where('status', $request->string('status')->toString());
        }

        $tickets = $query->paginate($perPage)->withQueryString();

        return $this->responder->paginated($tickets, QueueTicketResource::collection($tickets), 'Daftar antrean berhasil dimuat.');
    }

    public function checkIn(QueueCheckInRequest $request): JsonResponse
    {
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

    public function callNext(Request $request): JsonResponse
    {
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

        if (! $ticket) {
            return $this->responder->success(null, 'Tidak ada antrean menunggu saat ini.');
        }

        return $this->responder->success(new QueueTicketResource($ticket->load('booking')), 'Antrean berikutnya berhasil dipanggil.');
    }
}
