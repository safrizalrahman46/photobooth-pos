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

    $bootstrap = [
        'oldValues' => $oldValues ?? [],
        'errors' => $errors->all(),
        'routes' => [
            'landing' => route('landing'),
            'booking' => route('booking.customer'),
            'create' => route('booking.create'),
            'submit' => route('booking.customer.store'),
            'admin' => route('admin.login'),
            'queueBoard' => route('queue.board'),
        ],
        'navigation' => [
            ...$navigation->all(),
        ],
        'site' => [
            'brand_name' => $general['brand_name'] ?? config('app.name', 'Ready To Pict'),
            'short_name' => $general['short_name'] ?? 'Studio',
            'logo_url' => $general['logo_url'] ?? '/favicon.ico',
        ],
        'csrfToken' => csrf_token(),
    ];
@endphp

<x-layouts.public :title="'Data Pemesan - '.($general['brand_name'] ?? config('app.name', 'Ready To Pict'))">
    <div id="booking-customer-app"></div>
    <script id="booking-customer-app-props" type="application/json">@json($bootstrap)</script>
</x-layouts.public>
