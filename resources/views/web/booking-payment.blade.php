@php
    $general = $siteSettings['general'] ?? [];
    $paymentSettings = $siteSettings['payment'] ?? [];
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

<x-layouts.public :title="'Pembayaran Booking - '.($general['brand_name'] ?? config('app.name', 'Ready To Pict'))">
    <div id="booking-payment-app"></div>
    <script id="booking-payment-app-props" type="application/json">@json($bootstrap)</script>
</x-layouts.public>
