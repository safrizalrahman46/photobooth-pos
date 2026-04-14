@php
    $bootstrap = [
        'oldValues' => $oldValues ?? [],
        'errors' => $errors->all(),
        'routes' => [
            'landing' => route('landing'),
            'booking' => route('booking.customer'),
            'create' => route('booking.create'),
            'submit' => route('booking.customer.store'),
        ],
        'csrfToken' => csrf_token(),
    ];
@endphp

<x-layouts.public :title="'Data Pemesan - READY TO PICT'">
    <div id="booking-customer-app"></div>
    <script id="booking-customer-app-props" type="application/json">@json($bootstrap)</script>
</x-layouts.public>
