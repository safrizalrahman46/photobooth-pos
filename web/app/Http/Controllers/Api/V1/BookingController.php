<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\BookingStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\AdminConfirmBookingPaymentRequest;
use App\Http\Requests\AdminConfirmBookingRequest;
use App\Http\Requests\AdminDeclineBookingRequest;
use App\Http\Requests\StoreBookingRequest;
use App\Http\Requests\UpdateBookingStatusRequest;
use App\Http\Resources\BookingResource;
use App\Models\Booking;
use App\Services\AdminBookingManagementService;
use App\Services\BookingService;
use App\Support\ApiResponder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use RuntimeException;
use Symfony\Component\HttpFoundation\StreamedResponse;

class BookingController extends Controller
{
    public function __construct(
        private readonly BookingService $bookingService,
        private readonly ApiResponder $responder,
        private readonly AdminBookingManagementService $adminBookingService,
    ) {}

    public function index(Request $request): JsonResponse
    {
        abort_unless($request->user()?->can('booking.view'), 403);

        $perPage = min((int) $request->integer('per_page', 15), 100);

        $query = Booking::query()
            ->with(['branch', 'package', 'designCatalog', 'addOns', 'transaction.items', 'transaction.payments'])
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

        return $this->responder->paginated($bookings, BookingResource::collection($bookings), 'Daftar booking berhasil dimuat.');
    }

    public function store(StoreBookingRequest $request): JsonResponse
    {
        try {
            $booking = $this->bookingService->create($request->validated());

            return $this->responder->success(new BookingResource($booking->load('branch', 'package', 'designCatalog', 'addOns', 'transaction.items', 'transaction.payments')), 'Booking berhasil dibuat.', 201);
        } catch (RuntimeException $exception) {
            return $this->responder->error($exception->getMessage(), 422);
        }
    }

    public function show(Request $request, Booking $booking): JsonResponse
    {
        abort_unless($request->user()?->can('booking.view'), 403);

        return $this->responder->success(new BookingResource($booking->load('branch', 'package', 'designCatalog', 'addOns', 'transaction.items', 'transaction.payments')));
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

        return $this->responder->success(new BookingResource($booking->load('branch', 'package', 'designCatalog', 'addOns', 'transaction.items', 'transaction.payments')), 'Status booking berhasil diperbarui.');
    }

    public function confirmPayment(AdminConfirmBookingPaymentRequest $request, Booking $booking): JsonResponse
    {
        abort_unless($request->user()?->can('booking.manage'), 403);

        try {
            $transaction = $this->adminBookingService->confirmPayment(
                $booking,
                $request->validated(),
                (int) $request->user()->id
            );
        } catch (ValidationException $exception) {
            return $this->responder->error(
                $exception->validator->errors()->first() ?: 'Konfirmasi pembayaran gagal.',
                422,
                $exception->errors()
            );
        }

        return $this->responder->success([
            'booking' => new BookingResource($booking->refresh()->load('branch', 'package', 'designCatalog', 'addOns', 'transaction.items', 'transaction.payments')),
            'transaction_id' => (int) $transaction->id,
            'transaction_status' => (string) $transaction->status->value,
            'paid_amount' => (float) $transaction->paid_amount,
            'total_amount' => (float) $transaction->total_amount,
        ], 'Pembayaran booking berhasil dikonfirmasi.');
    }

    public function confirm(AdminConfirmBookingRequest $request, Booking $booking): JsonResponse
    {
        abort_unless($request->user()?->can('booking.manage'), 403);

        try {
            $booking = $this->adminBookingService->confirm(
                $booking,
                (int) $request->user()->id,
                (string) ($request->validated('reason') ?? '')
            );
        } catch (ValidationException $exception) {
            return $this->responder->error(
                $exception->validator->errors()->first() ?: 'Verifikasi booking gagal.',
                422,
                $exception->errors()
            );
        }

        return $this->responder->success(new BookingResource($booking->load('branch', 'package', 'designCatalog', 'addOns', 'transaction.items', 'transaction.payments')), 'Booking berhasil diverifikasi.');
    }

    public function decline(AdminDeclineBookingRequest $request, Booking $booking): JsonResponse
    {
        abort_unless($request->user()?->can('booking.manage'), 403);

        try {
            $booking = $this->adminBookingService->decline(
                $booking,
                (int) $request->user()->id,
                (string) ($request->validated('reason') ?? '')
            );
        } catch (ValidationException $exception) {
            return $this->responder->error(
                $exception->validator->errors()->first() ?: 'Decline booking gagal.',
                422,
                $exception->errors()
            );
        }

        return $this->responder->success(new BookingResource($booking->load('branch', 'package', 'designCatalog', 'addOns', 'transaction.items', 'transaction.payments')), 'Booking berhasil ditolak.');
    }

    public function transferProof(Request $request, Booking $booking): StreamedResponse
    {
        abort_unless($request->user()?->can('booking.view'), 403);

        $rawPath = trim((string) ($booking->transfer_proof_path ?? ''));
        $normalizedPath = $this->normalizePublicDiskPath($rawPath);

        abort_if($normalizedPath === '', 404, 'Transfer proof not found.');
        abort_unless(Storage::disk('public')->exists($normalizedPath), 404, 'Transfer proof file is missing.');

        return Storage::disk('public')->response($normalizedPath, basename($normalizedPath), [
            'Content-Disposition' => 'inline; filename="'.basename($normalizedPath).'"',
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
