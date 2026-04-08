<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Status Booking</title>
</head>
<body style="font-family: Arial, sans-serif; color: #1f2937;">
    <h2>Status booking kamu diperbarui</h2>
    <p>Halo {{ $booking->customer_name }}, status booking kamu sudah berubah.</p>
    <p><strong>Kode Booking:</strong> {{ $booking->booking_code }}</p>
    <p><strong>Status Baru:</strong> {{ $booking->status->value }}</p>
    <p>Jika ada pertanyaan, balas email ini atau hubungi WhatsApp kami.</p>
</body>
</html>
