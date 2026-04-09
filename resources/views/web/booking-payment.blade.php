@php
    $bootstrap = [
        'bookingPayload' => $bookingPayload,
        'branch' => $branch,
        'package' => $package,
        'designCatalog' => $designCatalog,
        'oldValues' => $oldValues ?? [],
        'errors' => $errors->all(),
        'routes' => [
            'back' => route('booking.create'),
            'store' => route('booking.store'),
        ],
        'csrfToken' => csrf_token(),
    ];
@endphp

<x-layouts.public :title="'Pembayaran Booking - READY TO PICT'">
    <div id="booking-payment-app"></div>
    <script id="booking-payment-app-props" type="application/json">@json($bootstrap)</script>
</x-layouts.public>
