@php
    $general = $siteSettings['general'] ?? [];
    $addOns = collect($walkInRequest->add_ons_json ?? [])->values();

    $bootstrap = [
        'request' => [
            'request_code' => $walkInRequest->request_code,
            'branch_name' => $walkInRequest->branch?->name ?? '-',
            'package_name' => $walkInRequest->package_name,
            'customer_name' => $walkInRequest->customer_name,
            'customer_phone' => $walkInRequest->customer_phone,
            'add_ons' => $addOns,
            'total_amount' => (float) $walkInRequest->total_amount,
            'status' => $walkInRequest->status,
            'expires_at' => $walkInRequest->expires_at?->toIso8601String(),
        ],
        'routes' => [
            'landing' => route('landing'),
            'booking' => route('booking.customer'),
            'walkIn' => route('walk-in.create'),
            'admin' => route('admin.login'),
            'queueBoard' => route('queue.board'),
        ],
        'site' => [
            'brand_name' => $general['brand_name'] ?? config('app.name', 'Ready To Pict'),
            'short_name' => $general['short_name'] ?? 'Studio',
            'logo_url' => asset('images/logo/logo.png'),
        ],
    ];
@endphp

<x-layouts.public :title="'Kode Walk-in - '.($general['brand_name'] ?? config('app.name', 'Ready To Pict'))">
    <div id="walk-in-success-app"></div>
    <script id="walk-in-success-app-props" type="application/json">@json($bootstrap)</script>
</x-layouts.public>
