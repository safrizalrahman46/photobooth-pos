@php
    $prefillPackage = old('package_id') ?: (request()->integer('package') ?: null);

    $oldValues = [
        'branch_id' => old('branch_id'),
        'package_id' => $prefillPackage,
        'design_catalog_id' => old('design_catalog_id'),
        'booking_date' => old('booking_date'),
        'booking_time' => old('booking_time'),
        'customer_name' => old('customer_name'),
        'customer_phone' => old('customer_phone'),
        'customer_email' => old('customer_email'),
        'notes' => old('notes'),
    ];

    $bootstrap = [
        'branches' => $branches->values(),
        'packages' => $packages->values(),
        'designCatalogs' => $designCatalogs->values(),
        'oldValues' => $oldValues,
        'errors' => $errors->all(),
        'routes' => [
            'landing' => route('landing'),
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
