@php
    $general = $siteSettings['general'] ?? [];
    $prefillBranch = old('branch_id') ?: ($prefillBranch ?? null);
    $prefillPackage = old('package_id') ?: ($prefillPackage ?? null);

    $bootstrap = [
        'branches' => $branches->values(),
        'packages' => $packages->values(),
        'addOns' => $addOns->values(),
        'oldValues' => [
            'branch_id' => $prefillBranch,
            'package_id' => $prefillPackage,
            'customer_name' => old('customer_name'),
            'customer_phone' => old('customer_phone'),
            'terms_accepted' => old('terms_accepted'),
            'addons' => old('addons', []),
        ],
        'errors' => $errors->all(),
        'routes' => [
            'landing' => route('landing'),
            'booking' => route('booking.customer'),
            'submit' => route('walk-in.store'),
            'admin' => route('admin.login'),
            'queueBoard' => route('queue.board'),
        ],
        'site' => [
            'brand_name' => $general['brand_name'] ?? config('app.name', 'Ready To Pict'),
            'short_name' => $general['short_name'] ?? 'Studio',
            'logo_url' => asset('images/logo/logo.png'),
        ],
        'csrfToken' => csrf_token(),
        'submissionKey' => $submissionKey,
    ];
@endphp

<x-layouts.public :title="'Self Walk-in - '.($general['brand_name'] ?? config('app.name', 'Ready To Pict'))">
    <div id="walk-in-request-app"></div>
    <script id="walk-in-request-app-props" type="application/json">@json($bootstrap)</script>
</x-layouts.public>
