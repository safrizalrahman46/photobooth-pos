@php
    $totalAmount = (float) ($booking->total_amount ?? 0);
    $paidAmount = (float) ($booking->paid_amount ?? 0);
    $paymentStatus = 'Belum Dibayar';

    if ($paidAmount > 0 && $totalAmount > 0 && $paidAmount >= $totalAmount) {
        $paymentStatus = 'Lunas';
    } elseif ($paidAmount > 0) {
        $paymentStatus = 'DP';
    }

    $bootstrap = [
        'booking' => [
            'booking_code' => (string) $booking->booking_code,
            'customer_name' => (string) $booking->customer_name,
            'customer_phone' => (string) $booking->customer_phone,
            'branch_name' => (string) ($booking->branch?->name ?? '-'),
            'package_name' => (string) ($booking->package?->name ?? '-'),
            'design_name' => $booking->designCatalog?->name,
            'date_text' => (string) ($booking->booking_date?->translatedFormat('l, d F Y') ?? '-'),
            'time_text' => (string) (($booking->start_at?->format('H:i') ?? '-') . ' - ' . ($booking->end_at?->format('H:i') ?? '-') . ' WIB'),
            'total_amount' => $totalAmount,
            'paid_amount' => $paidAmount,
            'payment_status' => $paymentStatus,
            'notes' => (string) ($booking->notes ?? ''),
        ],
        'routes' => [
            'booking' => route('booking.customer'),
            'landing' => route('landing'),
        ],
    ];
@endphp

<x-layouts.public :title="'Booking Berhasil - READY TO PICT'">
    <div id="booking-success-app"></div>
    <script id="booking-success-app-props" type="application/json">@json($bootstrap)</script>
</x-layouts.public>
