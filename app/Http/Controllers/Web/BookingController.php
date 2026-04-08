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

    public function store(StoreBookingRequest $request): RedirectResponse
    {
        $payload = $request->validated();

        $package = Package::query()->findOrFail($payload['package_id']);

        if ($package->branch_id !== null && (int) $package->branch_id !== (int) $payload['branch_id']) {
            return back()
                ->withErrors(['package_id' => 'Paket tidak tersedia untuk cabang ini.'])
                ->withInput();
        }

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

        $payload['source'] = BookingSource::Web;

        try {
            $booking = $this->bookingService->create($payload);

            return redirect()
                ->route('booking.success', $booking->booking_code)
                ->with('booking_created', true);
        } catch (RuntimeException $exception) {
            return back()
                ->withErrors(['booking_time' => $exception->getMessage()])
                ->withInput();
        }
    }

    public function success(Booking $booking): View
    {
        $booking->loadMissing(['branch', 'package', 'designCatalog']);

        return view('web.booking-success', [
            'booking' => $booking,
        ]);
    }
}
