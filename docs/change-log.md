# Change Log

## 2026-04-22

### Vue Admin Full Parity + Filament Soft Disable Control Plane

- Menambahkan control plane admin UI:
  - `config/admin_ui.php`
  - env baru: `ADMIN_UI_DRIVER`, `ADMIN_UI_BLOCK_FILAMENT_ROUTES`, `ADMIN_UI_LEGACY_REDIRECTS`
  - registrasi conditional `App\Providers\Filament\AdminPanelProvider` dari `AppServiceProvider` saat `ADMIN_UI_DRIVER=filament`.
- Menambahkan middleware global web `BlockFilamentRoutesWhenDisabled` untuk memblok `filament/*` saat runtime admin berada di mode Vue.
- Menambahkan parity endpoint web `admin.*` untuk resource yang sebelumnya dominan di Filament:
  - `branches`
  - `time-slots` (termasuk `generate` dan `bulk-bookable`)
  - `blackout-dates`
  - `payments` (manual add payment ke transaksi)
  - `printer-settings` (termasuk set default)
  - `app-settings` (`general|booking|payment`)
- Menambahkan service layer backend untuk menjaga pola controller tipis:
  - `AdminBranchService`
  - `AdminTimeSlotService`
  - `AdminBlackoutDateService`
  - `AdminPrinterSettingService`
  - `AdminPaymentService`
  - `AdminAppSettingService`
- Mengekstrak logic time slot dari `Api\V1\TimeSlotController` ke service reusable (`AdminTimeSlotService`) agar web admin dan API berbagi source of truth.
- Memperluas bootstrap payload `AdminDashboardController` dengan endpoint URL + initial payload modul parity baru.
- Memperluas `AdminDashboardDataService` untuk menyiapkan initial data modul baru (`branches`, `time_slots`, `blackout_dates`, `printer_settings`, `payments`, `payment_transaction_options`).
- Menambah modul Vue di `AdminDashboardApp.vue` + page baru:
  - `BranchesPage.vue`
  - `TimeSlotsPage.vue`
  - `BlackoutDatesPage.vue`
  - `PaymentsPage.vue`
  - `PrinterSettingsPage.vue`
  - `AppSettingsPage.vue`
- Menambahkan feature test `tests/Feature/AdminVueModulesTest.php` untuk baseline flow:
  - block filament probe route (vue mode)
  - auth guard admin dashboard
  - branch CRUD basic
  - overlap validation time slot
  - invalid app setting group.

## 2026-04-19

### Booking Add-Ons Persistence and Availability

- Menambahkan skema database add-ons:
  - Tabel master `add_ons` untuk daftar add-ons yang tersedia.
  - Tabel pivot `booking_add_ons` untuk mencatat add-ons per booking (qty, unit_price, line_total).
- Menambahkan model `AddOn` serta relasi Eloquent:
  - `Package::addOns()`
  - `Booking::addOns()`
- Menambahkan `AddOnSeeder` dan memasukkannya ke `DatabaseSeeder` agar data add-ons tersedia sejak seeding.
- Memperluas `AdminStoreBookingRequest` dan `AdminUpdateBookingRequest` dengan validasi payload `add_ons`.
- Memperluas `AdminBookingManagementService` agar:
  - Menyimpan/sinkron add-ons saat create dan update booking.
  - Menghitung ulang `total_amount` booking berdasarkan harga paket + total add-ons.
  - Menyinkronkan item transaksi booking (`booking` + `add_on`) saat transaksi sudah ada.
  - Menyertakan add-ons ke transaksi auto-generated saat konfirmasi pembayaran booking.
- Memperluas `AdminDashboardDataService`:
  - `bookingFormOptions()` kini mengirim daftar `add_ons` aktif ke frontend booking page.
  - Mapping row booking kini menyertakan `add_on_id` per item add-ons agar dapat dipakai untuk edit/detail.
- Memperluas UI `BookingsPage.vue`:
  - Menampilkan daftar add-ons tersedia di form create/edit booking.
  - Mendukung pemilihan add-ons + qty, dan mengirim payload `add_ons` ke backend.

### Queue Add Source Restriction (Booking + Walk-In)

- Menambahkan endpoint admin baru `POST /admin/queue/check-in` (`admin.queue.check-in`) untuk menambahkan antrean dari booking.
- Memperluas respons queue admin (`AdminQueueController`) agar selalu mengirim `queue_live` dan `queue_booking_options` setelah aksi `index`, `callNext`, `checkIn`, `transition`, dan `walkIn`.
- Menambahkan `queueCheckInUrl` pada bootstrap payload dashboard (`AdminDashboardController`) agar frontend bisa memanggil endpoint check-in booking.
- Menambahkan `queueBookingOptions()` pada `AdminDashboardDataService` untuk menyiapkan daftar booking eligible (status aktif dan belum punya tiket antrean) sebagai sumber Add Queue.
- Memperbarui `AdminDashboardApp.vue` dengan state + handler baru `addQueueBooking` dan wiring prop/event ke halaman queue.
- Memperbarui modal Add Queue pada `QueuePage.vue` menjadi dua sumber saja:
  - `Booking`: pilih booking dari daftar eligible.
  - `Walk In`: input manual customer (branch, date, nama, phone).
- Validasi teknis:
  - Route queue admin terdaftar lengkap (5 route termasuk `check-in`).
  - Build frontend `vite build` berhasil tanpa error.

### Admin Module Architecture Refactor (Form Request + Service)

- Menstandarkan pola untuk modul Admin `Packages`, `Designs`, `Users`, `Bookings`, dan `Queue`:
  - Validasi dipindahkan ke Form Request.
  - Logic bisnis dipindahkan ke Service.
  - Controller ditipiskan menjadi handler request/response.
- Menambahkan Form Request baru:
  - `AdminStorePackageRequest`, `AdminUpdatePackageRequest`
  - `AdminStoreDesignRequest`, `AdminUpdateDesignRequest`
  - `AdminStoreUserRequest`
  - `AdminStoreBookingRequest`, `AdminUpdateBookingRequest`
  - `AdminConfirmBookingRequest`, `AdminConfirmBookingPaymentRequest`
  - `QueueCallNextRequest`
- Menambahkan Service baru:
  - `AdminPackageService`
  - `AdminDesignService`
  - `AdminUserService`
  - `AdminBookingManagementService`
- Memperluas `QueueService` dengan method command-level agar rule check-in booking dan defaulting queue date tidak lagi ada di controller.
- Mengubah `AdminQueueController` menggunakan `AdminQueuePageService` untuk payload read queue, sehingga queue web page tidak lagi bergantung pada logic read di dashboard service.
- Menyesuaikan bootstrap queue awal di `AdminDashboardController` dan `Filament\Pages\AdminDashboard` agar menggunakan `AdminQueuePageService`.
- Menyesuaikan `Api\V1\QueueController` agar lebih tipis dengan `QueueCallNextRequest` dan delegasi check-in ke `QueueService`.
- Validasi teknis:
  - Pengecekan diagnostics editor pada file yang diubah: tidak ada error.
  - `php artisan route:list --name=admin.` berhasil.
  - `npm run build` berhasil.

### Queue Modular Gap Cleanup (No Duplicate Read Logic)

- Menghilangkan duplikasi read logic queue di `AdminDashboardDataService` dengan delegasi penuh ke `AdminQueuePageService`:
  - `queueLive()` sekarang memanggil `AdminQueuePageService::live()`.
  - `queueBookingOptions()` sekarang memanggil `AdminQueuePageService::bookingOptions()`.
  - `queueSnapshot()` sekarang disusun dari payload `AdminQueuePageService::live()` agar tidak query ulang data queue yang sama.
- Menambahkan `QueueIndexRequest` untuk validasi filter listing antrean API (`per_page`, `branch_id`, `queue_date`, `status`).
- Menambahkan `QueueReadService` sebagai lapisan read/query antrean terpisah dari `QueueService` (command/write).
- Menipiskan `Api\V1\QueueController@index` agar tidak membangun query manual lagi, tetapi mendelegasikan ke `QueueReadService`.
- Hasil akhir:
  - `QueueService` fokus ke command/write antrean.
  - `AdminQueuePageService` fokus ke read/payload queue page.
  - `QueueReadService` fokus ke listing/pagination API queue.

### Non-Queue API Modular Sweep

- Menstandarkan API controller non-queue agar konsisten pola modular (`Form Request -> Service -> Controller tipis`).
- Menambahkan Form Request untuk validasi filter/listing API:
  - `PackageIndexRequest`
  - `DesignCatalogIndexRequest`
  - `BookingIndexRequest`
  - `TransactionIndexRequest`
- Menambahkan Form Request untuk validasi endpoint yang sebelumnya inline:
  - `ReportSummaryRequest`
  - `ProfileUpdateRequest`
- Menambahkan service read per modul API agar query/pagination tidak lagi berada di controller:
  - `PackageReadService`
  - `DesignCatalogReadService`
  - `BookingReadService`
  - `TransactionReadService`
- Menambahkan service command/auth profile agar logic mutation/autentikasi tidak inline di controller:
  - `ProfileService`
  - `ApiAuthService`
- Menipiskan controller API:
  - `PackageController@index` mendelegasikan query ke `PackageReadService`.
  - `DesignCatalogController@index` mendelegasikan query ke `DesignCatalogReadService`.
  - `BookingController@index` mendelegasikan query ke `BookingReadService`.
  - `TransactionController@index` mendelegasikan query ke `TransactionReadService`.
  - `ProfileController@update` mendelegasikan update profil ke `ProfileService`.
  - `ReportController@summary` menggunakan `ReportSummaryRequest` (tanpa validasi inline).
  - `AuthController@login/logout` mendelegasikan autentikasi ke `ApiAuthService`.

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
