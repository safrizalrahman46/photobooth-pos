<?php

namespace App\Http\Controllers\Web;

use App\Enums\BookingSource;
use App\Http\Controllers\Controller;
use App\Http\Requests\SlotAvailabilityRequest;
use App\Http\Requests\StoreBookingRequest;
use App\Models\Booking;
use App\Models\Branch;
use App\Models\DesignCatalog;
use App\Models\Package;
use App\Services\BookingService;
use App\Services\SlotService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;
use RuntimeException;
use Throwable;

class BookingController extends Controller
{
    public function __construct(
        private readonly BookingService $bookingService,
        private readonly SlotService $slotService,
    ) {}

    public function create(): View
    {
        $branches = collect();
        $packages = collect();
        $designCatalogs = collect();

        try {
            $branches = Branch::query()
                ->where('is_active', true)
                ->orderBy('name')
                ->get(['id', 'name', 'timezone', 'address']);

            $packages = Package::query()
                ->where('is_active', true)
                ->orderBy('sort_order')
                ->orderBy('name')
                ->get(['id', 'name', 'description', 'duration_minutes', 'base_price', 'branch_id']);

            $designCatalogs = DesignCatalog::query()
                ->where('is_active', true)
                ->orderBy('sort_order')
                ->orderBy('name')
                ->get(['id', 'package_id', 'name', 'theme', 'preview_url']);
        } catch (Throwable) {
        }

        return view('web.booking-create', [
            'branches' => $branches,
            'packages' => $packages,
            'designCatalogs' => $designCatalogs,
        ]);
    }

    public function availability(SlotAvailabilityRequest $request): JsonResponse
    {
        $payload = $request->validated();

        $package = Package::query()->findOrFail($payload['package_id']);
        $branchId = (int) $payload['branch_id'];

        if ($package->branch_id !== null && (int) $package->branch_id !== $branchId) {
            return response()->json([
                'success' => false,
                'message' => 'Paket tidak tersedia di cabang yang dipilih.',
                'data' => [],
            ], 422);
        }

        $slots = $this->slotService
            ->getAvailability($payload['date'], (int) $payload['package_id'], $branchId)
            ->map(function (array $slot) {
                return [
                    'slot_id' => $slot['slot_id'],
                    'start_time' => $slot['start_time'],
                    'end_time' => $slot['end_time'],
                    'start_label' => substr((string) $slot['start_time'], 0, 5),
                    'end_label' => substr((string) $slot['end_time'], 0, 5),
                    'remaining_slots' => $slot['remaining_slots'],
                    'is_available' => $slot['is_available'],
                ];
            })
            ->values();

        return response()->json([
            'success' => true,
            'message' => 'Ketersediaan slot berhasil dimuat.',
            'data' => $slots,
        ]);
    }

    public function preparePayment(StoreBookingRequest $request): RedirectResponse
    {
        $payload = $request->validated();

        $validation = $this->validateBookingPayload($payload);

        if ($validation instanceof RedirectResponse) {
            return $validation;
        }

        $request->session()->put('booking.payment_payload', $payload);

        return redirect()->route('booking.payment');
    }

    public function payment(): View|RedirectResponse
    {
        $payload = session('booking.payment_payload');

        if (! is_array($payload)) {
            return redirect()
                ->route('booking.create')
                ->withErrors(['booking' => 'Lengkapi data booking terlebih dahulu sebelum ke halaman pembayaran.']);
        }

        $branch = Branch::query()->find($payload['branch_id'] ?? null);
        $package = Package::query()->find($payload['package_id'] ?? null);
        $designCatalog = null;

        if (! empty($payload['design_catalog_id'])) {
            $designCatalog = DesignCatalog::query()->find($payload['design_catalog_id']);
        }

        if (! $branch || ! $package) {
            session()->forget('booking.payment_payload');

            return redirect()
                ->route('booking.create')
                ->withErrors(['booking' => 'Data booking tidak lagi valid. Silakan pilih ulang paket dan jadwal.']);
        }

        return view('web.booking-payment', [
            'bookingPayload' => $payload,
            'branch' => $branch,
            'package' => $package,
            'designCatalog' => $designCatalog,
            'oldValues' => [
                'payment_type' => old('payment_type', 'full'),
            ],
        ]);
    }

    public function store(StoreBookingRequest $request): RedirectResponse
    {
        $payload = $request->validated();

        $validation = $this->validateBookingPayload($payload);

        if ($validation instanceof RedirectResponse) {
            return $validation;
        }

        $payload['source'] = BookingSource::Web;

        try {
            $booking = $this->bookingService->create($payload);
            $storedProofPath = $this->storeTransferProof(
                $request->file('transfer_proof'),
                $booking->booking_code
            );

            $booking->forceFill([
                'payment_gateway' => 'manual_transfer',
                'payment_url' => null,
                'payment_token' => null,
                'payment_reference' => null,
                'payment_payload' => [
                    'method' => 'manual_transfer',
                    'payment_type' => (string) ($payload['payment_type'] ?? 'full'),
                    'submitted_via' => 'web',
                ],
                'transfer_proof_path' => $storedProofPath,
                'transfer_proof_uploaded_at' => now(),
            ])->save();

            $request->session()->forget('booking.payment_payload');

            return redirect()
                ->route('booking.success', $booking->booking_code)
                ->with('booking_created', true)
                ->with('booking_payment_notice', 'Bukti pembayaran berhasil diunggah. Booking menunggu verifikasi admin.');
        } catch (Throwable $exception) {
            if (isset($booking) && $booking instanceof Booking) {
                $booking->delete();
            }

            return back()
                ->withErrors(['booking' => $exception->getMessage() ?: 'Gagal menyimpan booking.'])
                ->withInput();
        }
    }

    private function validateBookingPayload(array $payload): array|RedirectResponse
    {
        $package = Package::query()->findOrFail($payload['package_id']);

        if ($package->branch_id !== null && (int) $package->branch_id !== (int) $payload['branch_id']) {
            return back()
                ->withErrors(['package_id' => 'Paket tidak tersedia untuk cabang ini.'])
                ->withInput();
        }

        $design = null;

        if (! empty($payload['design_catalog_id'])) {
            $design = DesignCatalog::query()->find($payload['design_catalog_id']);

            if (! $design || (int) $design->package_id !== (int) $payload['package_id']) {
                return back()
                    ->withErrors(['design_catalog_id' => 'Desain tidak sesuai dengan paket yang dipilih.'])
                    ->withInput();
            }
        }

        $availableSlots = $this->slotService->getAvailability(
            $payload['booking_date'],
            (int) $payload['package_id'],
            (int) $payload['branch_id']
        );

        $selectedSlot = $availableSlots->first(function (array $slot) use ($payload) {
            return str_starts_with((string) $slot['start_time'], $payload['booking_time']);
        });

        if (! $selectedSlot || ! ($selectedSlot['is_available'] ?? false)) {
            return back()
                ->withErrors(['booking_time' => 'Slot tidak tersedia. Silakan pilih jam lain.'])
                ->withInput();
        }

        return [
            'package' => $package,
            'design' => $design,
            'slot' => $selectedSlot,
        ];
    }

    public function success(Booking $booking): View
    {
        $booking->loadMissing(['branch', 'package', 'designCatalog']);

        return view('web.booking-success', [
            'booking' => $booking,
        ]);
    }

    private function storeTransferProof(?UploadedFile $file, string $bookingCode): string
    {
        if (! $file instanceof UploadedFile) {
            throw new RuntimeException('Bukti pembayaran wajib diunggah.');
        }

        $directory = 'booking-transfer-proofs/'.now()->format('Y/m');
        $extension = strtolower($file->getClientOriginalExtension() ?: $file->extension() ?: 'jpg');
        $fileName = Str::slug($bookingCode).'-'.Str::lower(Str::random(10)).'.'.$extension;

        return $file->storeAs($directory, $fileName, 'public');
    }
}
