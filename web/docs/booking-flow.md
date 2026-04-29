# Booking Flow

## Ringkasan Alur Baru

Alur booking publik sekarang menggunakan 2 tahap utama sebelum pembayaran:

1. Page Data Pemesan + Persetujuan S&K
2. Page Booking Sesi Foto (tanggal, waktu, add-on)

Tujuan perubahan:

- Memisahkan input data pemesan ke page awal agar flow lebih jelas.
- Memastikan data pemesan dan persetujuan S&K selesai sebelum user masuk ke pemilihan jadwal.
- Menjaga tema UI tetap konsisten dengan halaman Booking Sesi Foto.

## Routing

Prefix route: `/booking`

- `GET /booking/data-pemesan` -> `booking.customer`
- `POST /booking/data-pemesan` -> `booking.customer.store`
- `GET /booking` -> `booking.create`
- `GET /booking/payment` -> `booking.payment`
- `POST /booking/payment` -> `booking.payment.prepare`
- `POST /booking` -> `booking.store`

## Halaman Data Pemesan

### Backend

- Controller method: `BookingController@customer`
- Controller method: `BookingController@storeCustomer`
- Session key untuk prefill: `booking.prefill_customer`

### Frontend

- Blade mount: `resources/views/web/booking-customer.blade.php`
- Vue app: `resources/js/booking/BookingCustomerApp.vue`

Field input:

- customer_name (required)
- customer_phone (required)
- customer_email (optional)
- notes (optional)
- terms_accepted (required, harus dicentang)

Output setelah submit:

- Simpan payload ke session prefill.
- Redirect ke `booking.create`.

## Prefill ke Booking Sesi Foto

Pada `BookingController@create`, data session `booking.prefill_customer` dikirim ke view sebagai `prefillValues`.

Pada `booking-create.blade.php`, nilai awal old form akan fallback ke `prefillValues`.

Dengan begitu halaman Booking Sesi Foto otomatis terisi:

- Nama pemesan
- Nomor HP
- Email
- Catatan

## Integrasi Vite Mount

`resources/js/app.js` sekarang me-mount app tambahan:

- mount node: `#booking-customer-app`
- props node: `#booking-customer-app-props`

## Entry Point Landing

Landing page CTA sudah diarahkan ke page baru:

- dari `booking.create` menjadi `booking.customer`

## S&K Wajib Centang

User tidak dapat lanjut ke Booking Sesi Foto jika belum menyetujui S&K.

- Frontend: tombol lanjut nonaktif jika checkbox S&K belum dicentang.
- Backend: validasi request `terms_accepted` dengan rule `accepted`.
