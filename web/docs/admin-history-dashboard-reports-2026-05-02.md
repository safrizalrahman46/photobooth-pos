# Admin Dashboard, History Perubahan, dan Reports - 2026-05-02

## Ringkasan

- Dashboard admin diringkas menjadi fokus pada `Revenue Overview`, `Queue Monitor` besar, `Cashier Performance` chart, dan `Alert Operasional` kecil.
- Modul `Activity Logs` diubah menjadi `History Perubahan` dengan data dari tabel `activity_logs` dan retensi 90 hari.
- Reports memakai stacked bar per hari untuk performa cashier dan export Excel di client-side menggunakan `xlsx`.
- Halaman Transactions sekarang menampilkan detail item transaksi dan riwayat pembayaran per transaksi.
- Badge sidebar Queue sekarang memakai total `waiting` dari antrean aktif, bukan `verified_waiting` saja.

## Perubahan Dashboard

- File UI utama: `resources/js/admin/pages/DashboardPage.vue`.
- Layout lama yang menampilkan banyak kartu dan list dipangkas agar owner langsung melihat empat blok inti.
- `Revenue Overview` sekarang benar-benar berupa chart, bukan lagi list progress bar.
- Chart revenue dashboard memakai kombinasi `bar` untuk revenue dan `line` untuk jumlah booking agar label visual sesuai dengan data yang ditampilkan.
- Data chart cashier dashboard tidak membuat endpoint baru; dashboard reuse payload `GET /admin/dashboard-report` dengan range default 7 hari.
- Alert kecil memakai `ownerHighlights` yang sudah disiapkan backend, sehingga tidak mengubah flow query dasar dashboard.

## History Perubahan

- Halaman `resources/js/admin/pages/ActivityLogsPage.vue` diubah label dan presentasi menjadi `History Perubahan`.
- Data tetap dikirim dari `AdminDashboardDataService::recentActivities()`, tetapi sekarang payload activity diperkaya dengan:
  - `message`
  - `label`
  - `details`
  - `changed_fields`
  - `time_text`
- Logging perubahan ditambahkan pada flow backend yang memang menjadi sumber perubahan data:
  - booking
  - queue
  - transaction/payment
  - inventory
  - package
  - add-on
  - design
  - user
  - branch
  - time slot
  - blackout date
  - printer setting
  - app/settings
- Implementasi logging mengikuti alur logic yang sudah ada, bukan bypass request/controller:
  - validasi tetap lewat Form Request atau `$request->validate()` yang sudah tersedia
  - perubahan bisnis tetap lewat service yang sudah dipakai web/API
  - logging hanya ditambahkan setelah perubahan berhasil dibuat/disimpan

## Retensi 90 Hari

- Retensi activity log diatur lewat `routes/console.php`.
- Command baru: `php artisan activity-logs:purge`.
- Schedule: mingguan setiap Senin pukul `02:00`.
- Service `ActivityLogger` ditambah method `purgeOlderThanDays()` agar purge tetap reuse satu tempat.

## Reports dan Export Excel

- File UI reports: `resources/js/admin/pages/ReportsPage.vue`.
- Dependency frontend baru:
  - `chart.js`
  - `xlsx`
- Backend report tetap memakai endpoint dan validasi yang sama:
  - `from`
  - `to`
  - `package_id`
  - `cashier_id`
- `AdminDashboardDataService::reportSummary()` diperluas dengan `cashier_daily_series` agar report dan dashboard bisa memakai sumber data yang sama.
- Batch refactor awal memindahkan owner logic analytics/report ke `app/Services/ReportService.php`, sementara `AdminDashboardDataService` tetap menjadi wrapper agar bootstrap payload dan endpoint admin tidak berubah.
- Batch refactor lanjutan memindahkan owner read payload ke service domain masing-masing:
  - `BookingReadService`
  - `TransactionReadService`
  - `ActivityLogger::recentRows()`
  - `AdminQueuePageService::snapshot()`
  - `InventoryService::managementPayload()`
  - `AdminPackageService::managementRows()`
  - `AdminAddOnService::managementRows()`
  - `AdminDesignService::managementRows()`
  - `AdminUserService::rows()` / `roleOptions()`
  - service existing untuk branches, time slots, blackout dates, printer settings, dan payments
- Setelah pemindahan ini, `AdminDashboardDataService` difungsikan ulang sebagai bootstrap aggregator tipis, bukan lagi lokasi utama query lintas modul.
- Chart performa cashier dibuat sebagai stacked bar per hari. Donut chart tidak dipakai.
- Export Excel dilakukan dari data report aktif di frontend, jadi tidak menambah request/endpoint baru.

## Transactions Detail

- File UI transaksi: `resources/js/admin/pages/TransactionsPage.vue`.
- Backend `recentTransactions()` sekarang mengirim lebih banyak data agar modul transaksi bisa menampilkan:
  - branch
  - customer phone
  - total/paid/remaining/change
  - item lines
  - payment history
- Dashboard tetap aman karena hanya memakai slice data transaksi terbaru, sementara halaman Transactions menampilkan detail lengkap dari payload yang sama.

## Queue Badge dan Blink

- Logic badge/blink sidebar di `resources/js/admin/AdminDashboardApp.vue` diubah dari `verified_waiting` ke total `waiting`.
- Perubahan ini hanya memengaruhi indikator UI sidebar.
- Rule bisnis antrean tetap sama:
  - booking harus verified dulu sebelum masuk antrean
  - walk-in tetap bisa langsung menjadi ticket `waiting`

## Komponen Baru

- `resources/js/admin/components/StackedBarChart.vue`
- Komponen ini dipakai di dashboard dan reports agar chart cashier daily menggunakan konfigurasi Chart.js yang sama.
- `resources/js/admin/components/RevenueOverviewChart.vue`
- Komponen ini dipakai khusus untuk chart `Revenue Overview` dashboard.

## Final UX Polish

- Dashboard:
  - antrean berikutnya sekarang menampilkan sumber ticket (`Booking` atau `Walk-in`) dan jam masuk queue.
  - alert operasional memiliki empty state agar layout tetap stabil saat tidak ada alert.
- Transactions:
  - ringkasan transaksi sekarang menampilkan jumlah item dan jumlah payment per row sebelum dibuka.
  - label total dirapikan menjadi `Visible Paid Amount` agar tidak menyesatkan sebagai total global harian.
  - list item dan payment diberi `max-height` + scroll lokal supaya halaman tetap nyaman saat transaksi panjang.
- Reports:
  - tombol export dinonaktifkan saat report sedang dimuat atau belum ada data.
  - ditambahkan ringkasan kecil jumlah hari, cashier aktif, dan paket tampil.
  - list harian/package/add-on/cashier memakai scroll lokal agar layout tidak terlalu panjang.
- History Perubahan:
  - label filter modul diubah ke format yang lebih mudah dibaca.
  - timeline diberi area scroll lokal untuk menjaga performa dan kenyamanan baca.

## Bundle dan Loading

- Page berikut sekarang di-load async dari `AdminDashboardApp.vue`:
  - `DashboardPage`
  - `TransactionsPage`
  - `ReportsPage`
  - `ActivityLogsPage`
- Fetch-on-enter modul admin sekarang juga mulai dirapikan melalui `resources/js/admin/moduleRegistry.js` agar watcher `activeModuleId` tidak lagi berulang untuk setiap modul.
- `StackedBarChart.vue` tidak lagi mengimpor `chart.js` secara eager saat load awal admin.
- `RevenueOverviewChart.vue` juga memakai dynamic import `chart.js` agar dashboard tetap ringan saat initial load app.
- `chart.js/auto` baru di-load saat komponen chart benar-benar dipakai.
- `xlsx` tetap di-load dinamis hanya saat tombol export dipakai.
- Hasil build sesudah perapihan:
  - chunk utama admin turun menjadi sekitar `479.62 kB`
  - `chart.js` terpisah ke chunk `auto-*.js`
  - page dashboard/report/transaction/history terpisah ke chunk masing-masing

## Catatan Alur Logic

- Tidak ada perubahan yang melewati validation/request flow lama.
- Dashboard report masih memanggil endpoint report yang sama dan tetap tunduk pada validasi controller `AdminDashboardReportController`.
- Queue page tetap memakai `AdminQueuePageService` dan `QueueService`; hanya badge sidebar yang berubah ke total waiting.
- Histori perubahan tidak membuat source of truth baru; ia merekam hasil mutasi yang dilakukan service/controller yang memang sudah dipakai modul web/API.
