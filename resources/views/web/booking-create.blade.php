@php
    $prefillValues = $prefillValues ?? [];
    $prefillPackage = old('package_id') ?: (request()->integer('package') ?: ($prefillValues['package_id'] ?? null));

    $oldValues = [
        'branch_id' => old('branch_id', $prefillValues['branch_id'] ?? null),
        'package_id' => $prefillPackage,
        'design_catalog_id' => old('design_catalog_id', $prefillValues['design_catalog_id'] ?? null),
        'booking_date' => old('booking_date', $prefillValues['booking_date'] ?? null),
        'booking_time' => old('booking_time', $prefillValues['booking_time'] ?? null),
        'customer_name' => old('customer_name', $prefillValues['customer_name'] ?? null),
        'customer_phone' => old('customer_phone', $prefillValues['customer_phone'] ?? null),
        'customer_email' => old('customer_email', $prefillValues['customer_email'] ?? null),
        'notes' => old('notes', $prefillValues['notes'] ?? null),
        'add_ons' => old('add_ons', []),
    ];

    $bootstrap = [
        'branches' => $branches->values(),
        'packages' => $packages->values(),
        'designCatalogs' => $designCatalogs->values(),
        'addOns' => ($addOns ?? collect())->values(),
        'oldValues' => $oldValues,
        'errors' => $errors->all(),
        'routes' => [
            'landing' => route('landing'),
            'booking' => route('booking.customer'),
            'availability' => route('booking.availability'),
            'payment' => route('booking.payment.prepare'),
            'store' => route('booking.store'),
        ],
        'csrfToken' => csrf_token(),
    ];
@endphp

<x-layouts.public :title="'Booking Online - READY TO PICT'">
    <div id="booking-app"></div>
    <script id="booking-app-props" type="application/json">@json($bootstrap)</script>
</x-layouts.public>
