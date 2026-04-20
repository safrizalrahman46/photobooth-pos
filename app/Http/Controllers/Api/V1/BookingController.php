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
<<<<<<< HEAD
        abort_unless($request->user()?->can('booking.view'), 403);

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

        if ($request->filled('booking_code')) {
            $bookingCode = mb_strtoupper(trim($request->string('booking_code')->toString()));
            $query->where('booking_code', 'like', "%{$bookingCode}%");
        }

        $bookings = $query->paginate($perPage)->withQueryString();
=======
        $payload = $request->validated();
        $perPage = (int) ($payload['per_page'] ?? 15);

        $bookings = $this->bookingReadService->paginate($payload, $perPage);
>>>>>>> fc7ace865dfae888f032ba57ff5855d596c41b93

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

    public function show(Request $request, Booking $booking): JsonResponse
    {
        abort_unless($request->user()?->can('booking.view'), 403);

        return $this->responder->success(new BookingResource($booking->load('package', 'designCatalog')));
    }

    public function updateStatus(UpdateBookingStatusRequest $request, Booking $booking): JsonResponse
    {
        abort_unless($request->user()?->can('booking.manage'), 403);

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
