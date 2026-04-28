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

    $statusValue = $booking->status?->value ?? (string) $booking->status;
    $hasTransferProof = filled($booking->transfer_proof_path);

    if ((float) $booking->paid_amount >= (float) $booking->total_amount && (float) $booking->total_amount > 0) {
        $paymentStatusLabel = 'Lunas';
    } elseif ((float) $booking->paid_amount > 0) {
        $paymentStatusLabel = 'DP';
    } else {
        $paymentStatusLabel = 'Belum Bayar';
    }

    if ($hasTransferProof) {
        $paymentMessage = 'Bukti transfer sudah diterima. Menunggu verifikasi admin.';
    } elseif ($booking->payment_type === 'dp50') {
        $paymentMessage = 'Menunggu upload bukti transfer DP 50% via QR BRI.';
    } else {
        $paymentMessage = 'Menunggu upload bukti transfer pelunasan via QR BRI.';
    }

    $bootstrap = [
        'booking' => [
            'booking_code' => (string) ($booking->booking_code ?? '-'),
            'customer_name' => (string) ($booking->customer_name ?? '-'),
            'customer_phone' => (string) ($booking->customer_phone ?? '-'),
            'branch_name' => (string) ($booking->branch?->name ?? '-'),
            'package_name' => (string) ($booking->package?->name ?? '-'),
            'design_name' => (string) ($booking->designCatalog?->name ?? ''),
            'date_text' => $booking->booking_date?->format('d M Y') ?? '-',
            'time_text' => trim(($booking->start_at?->format('H:i') ?? '--:--').' - '.($booking->end_at?->format('H:i') ?? '--:--')),
            'total_amount' => (float) ($booking->total_amount ?? 0),
            'paid_amount' => (float) ($booking->paid_amount ?? 0),
            'payment_status' => $paymentStatusLabel,
            'payment_message' => $paymentMessage,
            'payment_reference' => (string) ($booking->payment_reference ?? ''),
            'status' => (string) $statusValue,
            'notice' => (string) session('booking_payment_notice', ''),
            'notes' => (string) ($booking->notes ?? ''),
            'continue_payment_url' => ($booking->payment_gateway === 'midtrans' && in_array($booking->payment_type, ['full', 'dp50'], true) && $statusValue !== 'paid' && $booking->payment_url)
                ? (string) $booking->payment_url
                : '',
        ],
        'routes' => [
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
    ];
@endphp

<x-layouts.public :title="'Booking Berhasil - '.($general['brand_name'] ?? config('app.name', 'Ready To Pict'))">
    <div id="booking-success-app"></div>
    <script id="booking-success-app-props" type="application/json">@json($bootstrap)</script>
</x-layouts.public>
