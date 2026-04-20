@php
    $general = $siteSettings['general'] ?? [];
    $paymentSettings = $siteSettings['payment'] ?? [];
    $bootstrap = [
        'bookingPayload' => $bookingPayload,
        'branch' => $branch,
        'package' => $package,
        'designCatalog' => $designCatalog,
        'oldValues' => $oldValues ?? [],
        'paymentSettings' => $paymentSettings,
        'errors' => $errors->all(),
        'routes' => [
            'back' => route('booking.create'),
            'store' => route('booking.store'),
            'landing' => route('landing'),
            'queueBoard' => route('queue.board'),
        ],
        'site' => [
            'brand_name' => $general['brand_name'] ?? config('app.name', 'Ready To Pict'),
            'short_name' => $general['short_name'] ?? 'Studio',
            'logo_url' => $general['logo_url'] ?? '/favicon.ico',
        ],
        'csrfToken' => csrf_token(),
    ];
@endphp

<x-layouts.public :title="'Pembayaran Booking - '.($general['brand_name'] ?? config('app.name', 'Ready To Pict'))">
    <div id="booking-payment-app"></div>
    <script id="booking-payment-app-props" type="application/json">@json($bootstrap)</script>
</x-layouts.public>
