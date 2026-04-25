@php
    $general = $siteSettings['general'] ?? [];
    $ui = is_array($siteSettings['ui']['booking'] ?? null) ? $siteSettings['ui']['booking'] : [];
    $navigationConfig = is_array($ui['navigation'] ?? null) ? $ui['navigation'] : [];
    $navigation = collect($navigationConfig)
        ->map(function ($item, int $index): array {
            $routeName = trim((string) ($item['route'] ?? ''));
            $href = trim((string) ($item['href'] ?? ''));

            if ($routeName !== '') {
                try {
                    $href = route($routeName);
                } catch (\Throwable) {
                    $href = $href !== '' ? $href : '#';
                }
            }

            return [
                'key' => trim((string) ($item['key'] ?? '')) ?: 'nav-'.$index,
                'href' => $href !== '' ? $href : '#',
                'label' => trim((string) ($item['label'] ?? '')) ?: 'Menu',
            ];
        })
        ->values();
    $prefillCustomer = is_array($prefillCustomer ?? null) ? $prefillCustomer : [];
    $prefillPackage = old('package_id') ?: (request()->integer('package') ?: null);

    $oldValues = [
        'branch_id' => old('branch_id'),
        'package_id' => $prefillPackage,
        'design_catalog_id' => old('design_catalog_id'),
        'booking_date' => old('booking_date'),
        'booking_time' => old('booking_time'),
        'customer_name' => old('customer_name', (string) ($prefillCustomer['customer_name'] ?? '')),
        'customer_phone' => old('customer_phone', (string) ($prefillCustomer['customer_phone'] ?? '')),
        'customer_email' => old('customer_email', (string) ($prefillCustomer['customer_email'] ?? '')),
        'notes' => old('notes', (string) ($prefillCustomer['notes'] ?? '')),
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
            'availability' => route('booking.availability'),
            'payment' => route('booking.payment.prepare'),
            'store' => route('booking.store'),
            'booking' => route('booking.create'),
            'admin' => route('admin.login'),
            'queueBoard' => route('queue.board'),
        ],
        'navigation' => [
            ...$navigation->all(),
        ],
        'ui' => $ui,
        'site' => [
            'brand_name' => $general['brand_name'] ?? config('app.name', 'Ready To Pict'),
            'short_name' => $general['short_name'] ?? 'Studio',
            'logo_url' => $general['logo_url'] ?? '/favicon.ico',
        ],
        'csrfToken' => csrf_token(),
    ];
@endphp

<x-layouts.public :title="'Booking Online - '.($general['brand_name'] ?? config('app.name', 'Ready To Pict'))">
    <div id="booking-app"></div>
    <script id="booking-app-props" type="application/json">@json($bootstrap)</script>
</x-layouts.public>
