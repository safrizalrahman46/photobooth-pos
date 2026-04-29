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
use App\Services\BookingPaymentService;
use App\Services\SlotService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use RuntimeException;
use Throwable;

class BookingController extends Controller
{
    public function __construct(
        private readonly BookingService $bookingService,
        private readonly BookingPaymentService $bookingPaymentService,
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

            if (($payload['payment_type'] ?? 'onsite') === 'full') {
                try {
                    $booking = $this->bookingPaymentService->startOnlinePayment($booking);

                    $request->session()->forget('booking.payment_payload');

                    return redirect()->away($booking->payment_url);
                } catch (RuntimeException $exception) {
                    $booking->forceFill([
                        'payment_type' => 'onsite',
                        'payment_gateway' => null,
                        'payment_reference' => null,
                        'payment_token' => null,
                        'payment_url' => null,
                        'payment_payload' => null,
                        'payment_expires_at' => null,
                    ])->save();

                    session()->flash('booking_payment_notice', 'Pembayaran online belum tersedia. Booking diproses dengan metode bayar di studio.');
                }
            }

            $request->session()->forget('booking.payment_payload');

            return redirect()
                ->route('booking.success', $booking->booking_code)
                ->with('booking_created', true);
        } catch (RuntimeException $exception) {
            if (isset($booking) && $booking instanceof Booking) {
                $booking->delete();
            }

            return back()
                ->withErrors(['booking_time' => $exception->getMessage()])
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
}
