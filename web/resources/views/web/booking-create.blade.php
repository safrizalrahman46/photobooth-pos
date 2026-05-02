@php
    $general = $siteSettings['general'] ?? [];
    $sessionCustomer = is_array($customerPayload ?? null) ? $customerPayload : [];
    $prefillPackage = old('package_id') ?: ($prefillPackage ?? null);

    $oldValues = [
        'branch_id' => old('branch_id'),
        'package_id' => $prefillPackage,
        'design_catalog_id' => old('design_catalog_id'),
        'booking_date' => old('booking_date'),
        'booking_time' => old('booking_time'),
        'customer_name' => old('customer_name', $sessionCustomer['customer_name'] ?? null),
        'customer_phone' => old('customer_phone', $sessionCustomer['customer_phone'] ?? null),
        'customer_email' => old('customer_email', $sessionCustomer['customer_email'] ?? null),
        'notes' => old('notes', $sessionCustomer['notes'] ?? null),
    ];

    $bootstrap = [
        'branches' => $branches->values(),
        'packages' => $packages->values(),
        'designCatalogs' => $designCatalogs->values(),
        'addOns' => $addOns->values(),
        'oldValues' => $oldValues,
        'errors' => $errors->all(),
        'routes' => [
            'landing' => route('landing'),
            'booking' => route('booking.customer'),
            'availability' => route('booking.availability'),
            'payment' => route('booking.payment.prepare'),
            'store' => route('booking.store'),
            'queueBoard' => route('queue.board'),
        ],
        'site' => [
            'brand_name' => $general['brand_name'] ?? config('app.name', 'Ready To Pict'),
            'short_name' => $general['short_name'] ?? 'Studio',
            'logo_url' => $general['logo_url'] ?? asset('images/logo/logo.png'),
        ],
        'csrfToken' => csrf_token(),
    ];
@endphp

<x-layouts.public :title="'Booking Online - '.($general['brand_name'] ?? config('app.name', 'Ready To Pict'))">
    <div id="booking-app"></div>
    <script id="booking-app-props" type="application/json">@json($bootstrap)</script>
</x-layouts.public>
