<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\BookingStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\BookingIndexRequest;
use App\Http\Requests\StoreBookingRequest;
use App\Http\Requests\UpdateBookingStatusRequest;
use App\Http\Resources\BookingResource;
use App\Models\Booking;
use App\Services\BookingReadService;
use App\Services\BookingService;
use App\Support\ApiResponder;
use Illuminate\Http\JsonResponse;
use RuntimeException;

class BookingController extends Controller
{
    public function __construct(
        private readonly BookingReadService $bookingReadService,
        private readonly BookingService $bookingService,
        private readonly ApiResponder $responder,
    ) {}

    public function index(BookingIndexRequest $request): JsonResponse
    {
        $payload = $request->validated();
        $perPage = (int) ($payload['per_page'] ?? 15);

        $bookings = $this->bookingReadService->paginate($payload, $perPage);

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
