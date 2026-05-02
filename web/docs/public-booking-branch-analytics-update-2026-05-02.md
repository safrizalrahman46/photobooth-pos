# Public Booking, Branch QR, dan Admin Analytics Update - 2026-05-02

## Ringkasan

- Flow booking publik diubah menjadi `landing -> data pemesan -> form booking -> pembayaran -> sukses`.
- Slot booking untuk hari ini sekarang otomatis tidak bisa dipilih jika jam slot sudah lewat sesuai timezone branch.
- Preview `Hasil Foto` pada form booking sekarang diambil dinamis dari `sample_photos` package.
- QR pembayaran manual dipindahkan ke level branch menggunakan `payment_qr_url`.
- Admin bookings sekarang mendukung polling realtime ringan saat modul aktif.
- Queue Monitor di dashboard sekarang bisa langsung membuka page queue.
- Reports sekarang mendukung mode chart analytics yang bisa diganti, dan panel `Booking Status Mix` dihapus.

## Flow Booking Publik Baru

- Route baru:
  - `GET /booking/customer`
  - `POST /booking/customer`
- Controller yang diubah: `app/Http/Controllers/Web/BookingController.php`
- Halaman landing sekarang mengarahkan CTA booking ke langkah data pemesan lebih dulu.
- Data customer disimpan sementara di session sebelum user masuk ke form booking utama.
- Form booking utama (`BookingApp.vue`) tidak lagi menampilkan blok `Data Pemesan` yang duplikat.

## Dynamic Logo dan Sample Photos

- Public navbar sekarang fallback ke `resources/images/logo/logo.png` melalui bootstrap blade, bukan lagi `/favicon.ico`.
- File public blade yang diubah:
  - `resources/views/web/booking-customer.blade.php`
  - `resources/views/web/booking-create.blade.php`
  - `resources/views/web/booking-payment.blade.php`
  - `resources/views/web/booking-success.blade.php`
- Package sample photos diproses lewat `Package::resolvedSamplePhotos()` sebelum dikirim ke booking form.
- Route media publik untuk sample photo diaktifkan lewat:
  - `GET /media/package-samples/{path}`

## Slot Lewat Jam

- Service yang diubah: `app/Services/SlotService.php`
- Availability sekarang mempertimbangkan:
  - blackout date
  - durasi package terhadap slot
  - overlap booking aktif
  - jam slot yang sudah lewat untuk hari ini berdasarkan timezone branch

## QR Pembayaran Per Branch

- Migration baru:
  - `database/migrations/2026_05_02_120000_add_payment_qr_url_to_branches_table.php`
- Kolom baru branch:
  - `payment_qr_url`
- Backend yang diubah:
  - `app/Models/Branch.php`
  - `app/Http/Requests/AdminStoreBranchRequest.php`
  - `app/Http/Requests/AdminUpdateBranchRequest.php`
  - `app/Services/AdminBranchService.php`
- UI admin branch yang diubah:
  - `resources/js/admin/composables/useBranchesModule.js`
  - `resources/js/admin/pages/BranchesPage.vue`
- Payment page sekarang memprioritaskan `branch.payment_qr_url` sebelum fallback ke konfigurasi global lama.

## Admin Bookings Realtime

- File yang diubah:
  - `resources/js/admin/AdminDashboardApp.vue`
  - `resources/js/admin/moduleRegistry.js`
- Strategi realtime:
  - polling setiap sekitar 12 detik
  - hanya aktif saat modul `Bookings` sedang dibuka
  - berhenti saat user pindah modul atau app di-unmount

## Admin UI Cleanup

- Sidebar item yang dinonaktifkan:
  - `Designs`
  - `Payments`
  - `App Settings`
  - `Settings`
- Topbar:
  - tombol bell dihapus
  - tombol settings dihapus
  - search sekarang dihubungkan ke pencarian modul yang relevan, terutama `Bookings` dan `History Perubahan`
- Sidebar label dashboard sekarang mengikuti role user login:
  - `Owner Dashboard`
  - `Cashier Dashboard`

## Dashboard Queue Monitor

- File yang diubah:
  - `resources/js/admin/pages/DashboardPage.vue`
  - `resources/js/admin/AdminDashboardApp.vue`
- Queue Monitor sekarang memiliki aksi untuk langsung membuka page queue admin.

## Reports Analytics Mode

- Backend report diperluas di `app/Services/ReportService.php`.
- Panel `Booking Status Mix` dihapus dari UI reports.
- Reports sekarang menyediakan mode chart yang bisa dipilih, termasuk:
  - `Pendapatan per Cashier`
  - `Revenue per Hari`
  - `Booking per Hari`
  - `Transaksi per Hari`
  - `Walk-in per Hari`
  - `Ramai di Jam Berapa (Booking)`
  - `Ramai di Jam Berapa (Transaksi)`
- Komponen chart `resources/js/admin/components/StackedBarChart.vue` dibuat lebih generic agar bisa menangani:
  - stacked vs non-stacked
  - currency vs number axis

## Catatan Deploy

- Jalankan migration baru setelah pull perubahan ini:

```bash
php artisan migrate
```

- Lalu rebuild asset frontend:

```bash
npm run build
```
