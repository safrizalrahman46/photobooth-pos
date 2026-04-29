<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Booking Diterima</title>
</head>
<body style="font-family: Arial, sans-serif; color: #1f2937;">
    <h2>Booking kamu sudah diterima</h2>
    <p>Halo {{ $booking->customer_name }}, terima kasih sudah booking di Ready To Pict.</p>
    <p><strong>Kode Booking:</strong> {{ $booking->booking_code }}</p>
    <p><strong>Tanggal:</strong> {{ $booking->booking_date?->format('d M Y') }}</p>
    <p><strong>Jam:</strong> {{ $booking->start_at?->format('H:i') }}</p>
    <p>Status saat ini: <strong>{{ $booking->status->value }}</strong></p>
    <p>Kami akan mengirim update berikutnya setelah booking diproses.</p>
</body>
</html>
