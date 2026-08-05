<?php

namespace App\Http\Controllers\Web;

use App\Enums\BookingSource;
use App\Http\Controllers\Controller;
use App\Http\Requests\SlotAvailabilityRequest;
use App\Http\Requests\StoreBookingRequest;
use App\Models\Booking;
use App\Models\AddOn;
use App\Models\Branch;
use App\Models\DesignCatalog;
use App\Models\Package;
use App\Services\BookingService;
use App\Services\ReferralService;
use App\Services\SlotService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Intervention\Image\ImageManager;
use RuntimeException;
use Throwable;

class BookingController extends Controller
{
    private const CUSTOMER_SESSION_KEY = 'booking.customer_payload';
    private const PAYMENT_SESSION_KEY = 'booking.payment_payload';

    public function __construct(
        private readonly BookingService $bookingService,
        private readonly SlotService $slotService,
        private readonly ReferralService $referralService,
    ) {}

    public function customer(Request $request): View
    {
        $customerPayload = $request->session()->get(self::CUSTOMER_SESSION_KEY, []);
        $prefillPackage = old('package_id') ?: $request->integer('package') ?: (int) ($customerPayload['package_id'] ?? 0);

        return view('web.booking-customer', [
            'oldValues' => [
                'customer_name' => old('customer_name', (string) ($customerPayload['customer_name'] ?? '')),
                'customer_phone' => old('customer_phone', (string) ($customerPayload['customer_phone'] ?? '')),
                'customer_email' => old('customer_email', (string) ($customerPayload['customer_email'] ?? '')),
                'notes' => old('notes', (string) ($customerPayload['notes'] ?? '')),
                'terms_accepted' => old('terms_accepted', ($customerPayload['terms_accepted'] ?? false) ? '1' : ''),
                'package_id' => $prefillPackage > 0 ? $prefillPackage : null,
                'social_media_consent' => old('social_media_consent', ($customerPayload['social_media_consent'] ?? true) ? '1' : ''),
            ],
        ]);
    }

    public function storeCustomer(Request $request): RedirectResponse
    {
        $payload = $request->validate([
            'customer_name' => ['required', 'string', 'max:120'],
            'customer_phone' => ['required', 'string', 'max:30'],
            'customer_email' => ['nullable', 'email', 'max:255'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'terms_accepted' => ['accepted'],
            'package_id' => ['nullable', 'integer'],
            'social_media_consent' => ['nullable', 'boolean'],
        ]);

        $packageId = isset($payload['package_id']) ? (int) $payload['package_id'] : 0;
        if ($packageId > 0) {
            $packageExists = Package::query()
                ->whereKey($packageId)
                ->where('is_active', true)
                ->whereNull('deleted_at')
                ->exists();

            if (! $packageExists) {
                $packageId = 0;
            }
        }

        $request->session()->put(self::CUSTOMER_SESSION_KEY, [
            'customer_name' => (string) $payload['customer_name'],
            'customer_phone' => (string) $payload['customer_phone'],
            'customer_email' => (string) ($payload['customer_email'] ?? ''),
            'notes' => (string) ($payload['notes'] ?? ''),
            'terms_accepted' => true,
            'package_id' => $packageId > 0 ? $packageId : null,
            'social_media_consent' => filter_var($payload['social_media_consent'] ?? false, FILTER_VALIDATE_BOOLEAN),
        ]);

        return redirect()->route('booking.create', array_filter([
            'package' => $packageId > 0 ? $packageId : null,
        ]));
    }

    public function create(Request $request): View|RedirectResponse
    {
        $customerPayload = $request->session()->get(self::CUSTOMER_SESSION_KEY);

        if (! is_array($customerPayload) || blank($customerPayload['customer_name'] ?? null) || blank($customerPayload['customer_phone'] ?? null)) {
            return redirect()->route('booking.customer', array_filter([
                'package' => $request->integer('package') ?: null,
            ]));
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
                ->get(['id', 'name', 'description', 'duration_minutes', 'base_price', 'branch_id', 'sample_photos', 'code'])
                ->map(function (Package $package): array {
                    return [
                        'id' => (int) $package->id,
                        'name' => (string) $package->name,
                        'code' => (string) $package->code,
                        'description' => (string) ($package->description ?? ''),
                        'duration_minutes' => (int) $package->duration_minutes,
                        'base_price' => (float) $package->base_price,
                        'branch_id' => $package->branch_id ? (int) $package->branch_id : null,
                        'sample_photos' => $package->resolvedSamplePhotos(),
                    ];
                });

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

        $prefillPackage = old('package_id') ?: ($request->integer('package') ?: (int) ($customerPayload['package_id'] ?? 0));

        return view('web.booking-create', [
            'branches' => $branches,
            'packages' => $packages,
            'designCatalogs' => $designCatalogs,
            'addOns' => $addOns,
            'customerPayload' => $customerPayload,
            'prefillPackage' => $prefillPackage > 0 ? $prefillPackage : null,
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

        try {
            $payload['addons'] = $this->bookingService->resolveAddOnsForPackage(
                (int) $payload['package_id'],
                $payload['addons'] ?? []
            );
            $package = $validation['package'];
            $subtotal = (float) $package->base_price + (float) collect($payload['addons'])->sum('line_total');
            $referralPreview = $this->referralService->preview(
                $payload['referral_code'] ?? null,
                (int) $payload['branch_id'],
                (int) $payload['package_id'],
                $subtotal,
            );

            $payload['subtotal_amount'] = $subtotal;
            $payload['referral_discount_amount'] = (float) ($referralPreview['discount_amount'] ?? 0);
            $payload['final_amount'] = max(0, $subtotal - (float) ($referralPreview['discount_amount'] ?? 0));
        } catch (RuntimeException $exception) {
            return back()
                ->withErrors(['addons' => $exception->getMessage()])
                ->withInput();
        } catch (ValidationException $exception) {
            return back()
                ->withErrors($exception->errors())
                ->withInput();
        }

        $request->session()->put(self::PAYMENT_SESSION_KEY, $payload);

        return redirect()->route('booking.payment');
    }

    public function payment(): View|RedirectResponse
    {
        $payload = session(self::PAYMENT_SESSION_KEY);

        if (! is_array($payload)) {
            return redirect()
                ->route('booking.create')
                ->withErrors(['booking' => 'Lengkapi data booking terlebih dahulu sebelum ke halaman pembayaran.']);
        }

        $branch = Branch::query()->find($payload['branch_id'] ?? null);
        $package = Package::query()->find($payload['package_id'] ?? null);
        
        // Debug logging untuk troubleshoot QR issue
        if ($branch && config('app.debug')) {
            \Log::info('Payment page branch data:', [
                'branch_id' => $branch->id,
                'branch_name' => $branch->name,
                'payment_qr_url' => $branch->payment_qr_url,
            ]);
        }
        
        $designCatalog = null;

        if (! empty($payload['design_catalog_id'])) {
            $designCatalog = DesignCatalog::query()->find($payload['design_catalog_id']);
        }

        if (! $branch || ! $package) {
            session()->forget(self::PAYMENT_SESSION_KEY);

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

        $booking = null;
        $storedProofPath = null;

        try {
            // Step 1: Create booking — already wrapped in DB::transaction inside BookingService::create()
            // No nested transaction needed: we commit booking first, then update path separately
            $booking = $this->bookingService->create($payload);

            // Step 2: Store file to disk
            $storedProofPath = $this->storeTransferProof(
                $request->file('transfer_proof'),
                $booking->booking_code
            );

            // Step 3: Update booking with proof path — standalone query, no nested transaction
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

            $request->session()->forget([self::PAYMENT_SESSION_KEY, self::CUSTOMER_SESSION_KEY]);

            return redirect()
                ->route('booking.success', $booking->booking_code)
                ->with('booking_created', true)
                ->with('booking_payment_notice', 'Bukti pembayaran berhasil diunggah. Booking menunggu verifikasi admin.');
        } catch (Throwable $exception) {
            // Cleanup: hapus file orphan jika sudah tersimpan
            if ($storedProofPath !== null && Storage::disk('public')->exists($storedProofPath)) {
                Storage::disk('public')->delete($storedProofPath);
            }

            // Cleanup: hapus booking orphan jika sudah dibuat (DB mungkin kembali normal)
            if (isset($booking) && $booking instanceof Booking && $booking->exists) {
                try {
                    $booking->delete();
                } catch (Throwable) {
                    // DB masih down — booking tetap ada tanpa path, user bisa coba lagi
                }
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
        $baseName = Str::slug($bookingCode).'-'.Str::lower(Str::random(10));

        try {
            $manager = ImageManager::gd();
            $image = $manager->read($file->getRealPath());

            $image->scaleDown(width: 1200, height: 1200);

            $encoded = $image->toWebp(quality: 80)->encode();

            $fileName = $baseName.'.webp';
            $fullPath = $directory.'/'.$fileName;

            Storage::disk('public')->put($fullPath, $encoded);

            return $fullPath;
        } catch (Throwable $e) {
            $extension = strtolower($file->getClientOriginalExtension() ?: $file->extension() ?: 'jpg');
            $fileName = $baseName.'.'.$extension;
            $fullPath = $directory.'/'.$fileName;

            $storedPath = $file->storeAs($directory, $fileName, 'public');

            if ($storedPath === false) {
                throw new RuntimeException('Gagal menyimpan bukti pembayaran. Coba lagi.');
            }

            return $storedPath;
        }
    }
}
