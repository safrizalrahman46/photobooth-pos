# Change Log

## 2026-04-15

### Admin Dashboard Vue 1:1 Rewrite

- Merombak `AdminDashboardApp.vue` ke struktur desain dashboard utama 1:1 (hero header, summary cards, revenue chart, queue monitor, booking monitoring, recent transactions, activity log).
- Menghubungkan seluruh blok utama ke payload backend yang sudah ada (`summaryCards`, `revenueOverview`, `queueLive`, `initialRows`, `initialPagination`).
- Mempertahankan interaksi server-driven pada tabel booking (`search`, `status`, dan pagination) melalui endpoint `admin.dashboard.data`.

### Dashboard Data Payload Adjustment

- Memperluas data `recentTransactions()` di `AdminDashboardDataService` dengan field `customer` (dari relasi booking) dan `method` (dari pembayaran terbaru) untuk memenuhi kebutuhan tampilan transaksi baru.

## 2026-04-14

### Booking Public Flow

- Menambahkan page baru sebelum booking sesi foto: Data Pemesan + Pilih Paket.
- Menambahkan route baru:
  - `GET /booking/data-pemesan` (`booking.customer`)
  - `POST /booking/data-pemesan` (`booking.customer.store`)
- Menambahkan halaman Vue baru `BookingCustomerApp.vue` dengan tema visual yang konsisten dengan halaman Booking Sesi Foto.
- Menambahkan session prefill (`booking.prefill_customer`) untuk mengisi otomatis data customer di halaman `booking.create`.
- Mengubah CTA landing agar masuk ke page Data Pemesan terlebih dahulu.

### Booking Customer Step Adjustment

- Menghapus pilihan paket dari page Data Pemesan, karena paket dipilih di page Booking Sesi Foto.
- Menambahkan daftar S&K di page Data Pemesan:
  - Baca terlebih dahulu S&K.
  - Saya bersedia datang minimal 10 menit sebelum sesi dimulai.
  - Jika terlambat waktu sesi dihitung sesuai jadwal booking.
  - Booking dianggap sah setelah melakukan pembayaran.
  - Saya bersedia menjaga properti studio dan bertanggung jawab atas kerusakan akibat kelalaian.
- Menambahkan checkbox persetujuan S&K yang wajib dicentang sebelum tombol lanjut aktif.
- Menambahkan validasi backend `terms_accepted` (`accepted`) agar tidak bisa bypass tanpa centang S&K.
- Nomor HP pada Data Pemesan dibatasi angka saja (huruf tidak diperbolehkan) di frontend dan backend.

### Booking Confirmation UI

- Menyesuaikan tampilan halaman konfirmasi setelah pembayaran agar selaras dengan referensi `UI_Booking`.
- Menambahkan blok `Detail Booking Confirm` dengan susunan ringkas: data pemesan, jadwal, paket, total, dibayar, dan status pembayaran.
- Memperbarui gaya visual halaman sukses menjadi pola card putih + aksen warna yang konsisten dengan halaman pembayaran.
- Mengubah halaman konfirmasi booking agar dirender menggunakan Vue (`BookingSuccessApp.vue`) melalui mount point Blade.
- Menambahkan motion bertahap (staggered reveal + success icon pulse) pada halaman konfirmasi untuk meningkatkan kesan interaksi frontend.

### Admin Login Theme

- Menyetel ulang tone warna login ke palet biru-slate yang konsisten dengan Form Booking Sesi Foto (`#F8FAFC`, `#1F2937`, `#2563EB`).

### Owner Feature on /admin (Vue)

- Mengimplementasikan fitur owner di halaman `/admin` menggunakan frontend Vue pada dashboard utama.
- Menambahkan `Owner Feature Modules` (shortcut cepat ke bookings, transactions, queue, packages, design catalogs, users).
- Menambahkan `ownerHighlights` untuk ringkasan operasional owner (pendapatan hari ini, booking aktif, antrean menunggu, transaksi belum lunas).
- Menambahkan panel `Queue Snapshot Hari Ini`, `Aktivitas Terbaru`, dan `Transaksi Terbaru`.
- Memperluas `AdminDashboardDataService` untuk menyuplai data owner-centric ke Vue bootstrap payload.
- Menambahkan navbar owner yang sticky pada dashboard Vue untuk navigasi cepat antar modul owner dengan indikator menu aktif.

### Dashboard Admin (Filament + Vue)

- Menambahkan dashboard admin kustom berbasis Vue yang dirender di page Filament.
- Menambahkan bootstrap data dari backend (`initialStats`, `initialRows`, `initialPagination`, `dataUrl`).
- Menambahkan endpoint server-driven data dashboard:
  - `GET /admin/dashboard-data`
  - filter `search`, `status`, `per_page`
  - response rows + pagination
- Menambahkan service `AdminDashboardDataService` untuk query, mapping status UI, transform row, dan statistik.
- Menambahkan lifecycle mount Vue yang kompatibel dengan navigasi Livewire/Filament (`livewire:navigated`) dan proteksi double-mount.
- Memastikan asset Vite admin panel memuat CSS dan JS aplikasi di Filament provider melalui render hook.
- Menjalankan validasi build frontend (`npm run build`) dan cache clear (`php artisan optimize:clear`).

### Booking DatePicker

- Memperbaiki logika disabled date menggunakan perbandingan level hari agar lebih konsisten.
- Memperbaiki style disabled state (muted, non-interactive) untuk tanggal sebelum hari ini.
