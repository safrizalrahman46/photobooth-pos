<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\BookingStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreBookingRequest;
use App\Http\Requests\UpdateBookingStatusRequest;
use App\Http\Resources\BookingResource;
use App\Models\Booking;
use App\Services\BookingService;
use App\Support\ApiResponder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use RuntimeException;

class BookingController extends Controller
{
    public function __construct(
        private readonly BookingService $bookingService,
        private readonly ApiResponder $responder,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $perPage = min((int) $request->integer('per_page', 15), 100);

        $query = Booking::query()
            ->with(['package', 'designCatalog'])
            ->orderByDesc('booking_date')
            ->orderByDesc('start_at');

        if ($request->filled('branch_id')) {
            $query->where('branch_id', $request->integer('branch_id'));
        }

        if ($request->filled('status')) {
            $query->where('status', $request->string('status')->toString());
        }

        if ($request->filled('date')) {
            $query->whereDate('booking_date', $request->string('date')->toString());
        }

        $bookings = $query->paginate($perPage)->withQueryString();

        return $this->responder->paginated($bookings, BookingResource::collection($bookings), 'Daftar booking berhasil dimuat.');
    }

    public function store(StoreBookingRequest $request): JsonResponse
    {
        try {
            $booking = $this->bookingService->create($request->validated());

            return $this->responder->success(new BookingResource($booking->load('package', 'designCatalog')), 'Booking berhasil dibuat.', 201);
        } catch (RuntimeException $exception) {
            return $this->responder->error($exception->getMessage(), 422);
        }
    }

    public function show(Booking $booking): JsonResponse
    {
        return $this->responder->success(new BookingResource($booking->load('package', 'designCatalog')));
    }

    public function updateStatus(UpdateBookingStatusRequest $request, Booking $booking): JsonResponse
    {
        $payload = $request->validated();
        $actorId = $request->user()?->id;
        $status = BookingStatus::from($payload['status']);

        $booking = $this->bookingService->updateStatus(
            $booking,
            $status,
            $actorId,
            $payload['reason'] ?? null
        );

        return $this->responder->success(new BookingResource($booking->load('package', 'designCatalog')), 'Status booking berhasil diperbarui.');
    }
}
