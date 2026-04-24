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
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use Throwable;

class BookingController extends Controller
{
    public function __construct(
        private readonly BookingService $bookingService,
        private readonly SlotService $slotService,
    ) {}

    public function customer(Request $request): View
    {
        $prefill = $request->session()->get('booking.prefill_customer', []);

        if (! is_array($prefill)) {
            $prefill = [];
        }

        return view('web.booking-customer', [
            'oldValues' => [
                'customer_name' => old('customer_name', (string) ($prefill['customer_name'] ?? '')),
                'customer_phone' => old('customer_phone', (string) ($prefill['customer_phone'] ?? '')),
                'customer_email' => old('customer_email', (string) ($prefill['customer_email'] ?? '')),
                'notes' => old('notes', (string) ($prefill['notes'] ?? '')),
                'terms_accepted' => old('terms_accepted', 0),
            ],
        ]);
    }

    public function storeCustomer(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'customer_name' => ['required', 'string', 'max:120'],
            'customer_phone' => ['required', 'string', 'max:30', 'regex:/^[0-9]+$/'],
            'customer_email' => ['nullable', 'email', 'max:120'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'terms_accepted' => ['accepted'],
        ]);

        $request->session()->put('booking.prefill_customer', [
            'customer_name' => trim((string) ($validated['customer_name'] ?? '')),
            'customer_phone' => preg_replace('/\D+/', '', (string) ($validated['customer_phone'] ?? '')),
            'customer_email' => trim((string) ($validated['customer_email'] ?? '')),
            'notes' => trim((string) ($validated['notes'] ?? '')),
        ]);

        return redirect()->route('booking.create');
    }

    public function create(Request $request): View|RedirectResponse
    {
        $prefill = $request->session()->get('booking.prefill_customer', []);

        if (! is_array($prefill)) {
            $prefill = [];
        }

        $hasPrefillCustomer = filled($prefill['customer_name'] ?? null) && filled($prefill['customer_phone'] ?? null);
        $hasOldCustomer = filled(old('customer_name')) && filled(old('customer_phone'));

        if (! $hasPrefillCustomer && ! $hasOldCustomer) {
            return redirect()->route('booking.customer');
        }

        $branches = collect();
        $packages = collect();
        $designCatalogs = collect();
        $addOns = collect();

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
                ->get(['id', 'package_id', 'code', 'name', 'description', 'price', 'max_qty']);
        } catch (Throwable) {
        }

        return view('web.booking-create', [
            'branches' => $branches,
            'packages' => $packages,
            'designCatalogs' => $designCatalogs,
            'addOns' => $addOns,
            'prefillCustomer' => $prefill,
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
                'transfer_reference' => old('transfer_reference', ''),
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
        $transferProofPath = null;

        try {
            $transferProofFile = $request->file('transfer_proof');

            if ($transferProofFile) {
                $transferProofPath = $transferProofFile->store('booking-transfer-proofs', 'public');
            }

            $payload['payment_gateway'] = 'manual_qr_bri';
            $payload['payment_reference'] = $payload['transfer_reference'] ?? null;
            $payload['transfer_proof_path'] = $transferProofPath;
            $payload['transfer_proof_uploaded_at'] = now();
            $payload['payment_payload'] = [
                'channel' => 'manual_qr_bri',
                'transfer_reference' => $payload['transfer_reference'] ?? null,
                'declared_payment_type' => $payload['payment_type'] ?? 'full',
            ];

            $booking = $this->bookingService->create($payload);
            session()->flash('booking_payment_notice', 'Booking berhasil. Bukti transfer QR sudah diterima dan menunggu verifikasi admin.');

            $request->session()->forget('booking.payment_payload');

            return redirect()
                ->route('booking.success', $booking->booking_code)
                ->with('booking_created', true);
        } catch (RuntimeException $exception) {
            if ($transferProofPath) {
                Storage::disk('public')->delete($transferProofPath);
            }

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
