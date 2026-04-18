<?php

namespace App\Http\Controllers\Web;

use App\Enums\BookingSource;
use App\Http\Controllers\Controller;
use App\Http\Requests\SlotAvailabilityRequest;
use App\Http\Requests\StoreBookingRequest;
use App\Models\AddOn;
use App\Models\Booking;
use App\Models\Branch;
use App\Models\DesignCatalog;
use App\Models\Package;
use App\Services\BookingService;
use App\Services\SlotService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use RuntimeException;
use Throwable;

class BookingController extends Controller
{
    public function __construct(
        private readonly BookingService $bookingService,
        private readonly SlotService $slotService,
    ) {}

    public function customer(): View
    {
        $prefill = session('booking.prefill_customer');

        return view('web.booking-customer', [
            'oldValues' => [
                'customer_name' => old('customer_name', (string) data_get($prefill, 'customer_name', '')),
                'customer_phone' => old('customer_phone', (string) data_get($prefill, 'customer_phone', '')),
                'customer_email' => old('customer_email', (string) data_get($prefill, 'customer_email', '')),
                'notes' => old('notes', (string) data_get($prefill, 'notes', '')),
                'terms_accepted' => old('terms_accepted', (bool) data_get($prefill, 'terms_accepted', false)),
            ],
        ]);
    }

    public function storeCustomer(Request $request): RedirectResponse
    {
        $payload = $request->validate([
            'customer_name' => ['required', 'string', 'max:120'],
            'customer_phone' => ['required', 'string', 'max:30', 'regex:/^[0-9]+$/'],
            'customer_email' => ['nullable', 'email', 'max:120'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'terms_accepted' => ['accepted'],
        ]);

        $request->session()->put('booking.prefill_customer', [
            'customer_name' => (string) $payload['customer_name'],
            'customer_phone' => (string) $payload['customer_phone'],
            'customer_email' => (string) ($payload['customer_email'] ?? ''),
            'notes' => (string) ($payload['notes'] ?? ''),
            'terms_accepted' => true,
        ]);

        return redirect()->route('booking.create');
    }

    public function create(): View
    {
        $branches = collect();
        $packages = collect();
        $designCatalogs = collect();
        $addOns = collect();
        $prefillValues = session('booking.prefill_customer', []);

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

            $addOns = AddOn::query()
                ->where('is_active', true)
                ->orderBy('sort_order')
                ->orderBy('name')
                ->get(['id', 'package_id', 'code', 'name', 'price', 'max_qty']);
        } catch (Throwable) {
        }

        return view('web.booking-create', [
            'branches' => $branches,
            'packages' => $packages,
            'designCatalogs' => $designCatalogs,
            'addOns' => $addOns,
            'prefillValues' => is_array($prefillValues) ? $prefillValues : [],
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
            ->getAvailability(
                $payload['date'],
                (int) $payload['package_id'],
                $branchId,
                isset($payload['booking_id']) ? (int) $payload['booking_id'] : null,
            )
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

        $payload['add_ons'] = $validation['add_ons_payload'];
        $payload['addons'] = $validation['add_ons_summary'];
        $payload['total_amount'] = $validation['total_amount'];

        $request->session()->put('booking.payment_payload', $payload);

        return redirect()->route('booking.payment');
    }

    public function payment(): View|RedirectResponse
    {
        $payload = session('booking.payment_payload');

        if (! is_array($payload)) {
            return redirect()
                ->route('booking.customer')
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
                ->route('booking.customer')
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

        $payload['add_ons'] = $validation['add_ons_payload'];
        $payload['total_amount'] = $validation['total_amount'];

        $payload['source'] = BookingSource::Web;

        try {
            $booking = $this->bookingService->create($payload);

            $request->session()->forget(['booking.payment_payload', 'booking.prefill_customer']);

            return redirect()
                ->route('booking.success', $booking->booking_code)
                ->with('booking_created', true);
        } catch (RuntimeException $exception) {
            return back()
                ->withErrors(['booking' => $exception->getMessage()])
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

        $resolvedAddOns = $this->resolveSelectedAddOns($payload, $package);

        if ($resolvedAddOns instanceof RedirectResponse) {
            return $resolvedAddOns;
        }

        $totalAmount = (float) $package->base_price + (float) collect($resolvedAddOns)->sum('line_total');

        return [
            'package' => $package,
            'design' => $design,
            'slot' => $selectedSlot,
            'add_ons_payload' => collect($resolvedAddOns)
                ->map(fn (array $item): array => [
                    'add_on_id' => (int) $item['add_on_id'],
                    'qty' => (int) $item['qty'],
                ])
                ->values()
                ->all(),
            'add_ons_summary' => collect($resolvedAddOns)
                ->map(fn (array $item): array => [
                    'id' => (int) $item['id'],
                    'code' => (string) $item['code'],
                    'label' => (string) $item['name'],
                    'qty' => (int) $item['qty'],
                    'price' => (float) $item['unit_price'],
                    'line_total' => (float) $item['line_total'],
                ])
                ->values()
                ->all(),
            'total_amount' => $totalAmount,
        ];
    }

    private function resolveSelectedAddOns(array $payload, Package $package): array|RedirectResponse
    {
        $requested = collect($payload['add_ons'] ?? [])
            ->filter(fn ($row): bool => is_array($row))
            ->map(fn (array $row): array => [
                'add_on_id' => (int) ($row['add_on_id'] ?? 0),
                'qty' => (int) ($row['qty'] ?? 0),
            ])
            ->filter(fn (array $row): bool => $row['add_on_id'] > 0 && $row['qty'] > 0)
            ->groupBy('add_on_id')
            ->map(fn ($group, $addOnId): array => [
                'add_on_id' => (int) $addOnId,
                'qty' => (int) collect($group)->sum('qty'),
            ])
            ->values();

        if ($requested->isEmpty()) {
            return [];
        }

        $ids = $requested->pluck('add_on_id')->all();

        $addOns = AddOn::query()
            ->whereIn('id', $ids)
            ->where('is_active', true)
            ->get(['id', 'package_id', 'code', 'name', 'price', 'max_qty'])
            ->keyBy('id');

        if ($addOns->count() !== count($ids)) {
            return back()
                ->withErrors(['add_ons' => 'Ada add-on yang tidak tersedia.'])
                ->withInput();
        }

        $resolved = [];

        foreach ($requested as $item) {
            $addOn = $addOns->get((int) $item['add_on_id']);

            if (! $addOn) {
                return back()
                    ->withErrors(['add_ons' => 'Ada add-on yang tidak tersedia.'])
                    ->withInput();
            }

            if ($addOn->package_id !== null && (int) $addOn->package_id !== (int) $package->id) {
                return back()
                    ->withErrors(['add_ons' => 'Add-on tidak valid untuk paket yang dipilih.'])
                    ->withInput();
            }

            $maxQty = max(1, (int) $addOn->max_qty);
            $qty = (int) $item['qty'];

            if ($qty > $maxQty) {
                return back()
                    ->withErrors(['add_ons' => sprintf('Maksimum qty untuk %s adalah %d.', (string) $addOn->name, $maxQty)])
                    ->withInput();
            }

            $unitPrice = (float) $addOn->price;

            $resolved[] = [
                'id' => (int) $addOn->id,
                'add_on_id' => (int) $addOn->id,
                'code' => (string) $addOn->code,
                'name' => (string) $addOn->name,
                'qty' => $qty,
                'unit_price' => $unitPrice,
                'line_total' => $qty * $unitPrice,
            ];
        }

        return $resolved;
    }

    public function success(Booking $booking): View
    {
        $booking->loadMissing(['branch', 'package', 'designCatalog']);

        return view('web.booking-success', [
            'booking' => $booking,
        ]);
    }
}
