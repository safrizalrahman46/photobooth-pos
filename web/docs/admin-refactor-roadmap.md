# Admin Refactor Roadmap

## Tujuan

- Mengurangi coupling besar di `app/Services/AdminDashboardDataService.php`.
- Mengurangi logic orchestration berulang di `resources/js/admin/AdminDashboardApp.vue`.
- Menjaga parity endpoint, validasi, dan shape payload selama refactor bertahap.

## Boundary Saat Ini

- `AdminDashboardDataService` sekarang berperan sebagai bootstrap aggregator tipis.
- Owner report/analytics:
  - `ReportService`
- Owner booking table read:
  - `BookingReadService`
- Owner transaksi terbaru:
  - `TransactionReadService`
- Owner history perubahan terbaru:
  - `ActivityLogger::recentRows()`
- Owner queue live/snapshot:
  - `AdminQueuePageService`
- Owner package/add-on/design/user/inventory read payload:
  - `AdminPackageService`
  - `AdminAddOnService`
  - `AdminDesignService`
  - `AdminUserService`
  - `InventoryService`
- Owner branch/time-slot/blackout/printer/payment read payload:
  - `AdminBranchService`
  - `AdminTimeSlotService`
  - `AdminBlackoutDateService`
  - `AdminPrinterSettingService`
  - `AdminPaymentService`

## Batch Status

- Batch 1: selesai
  - report/analytics dipindah ke `ReportService`
- Batch 2: selesai
  - booking rows/filter/sort/pagination dipindah ke `BookingReadService`
- Batch 3: selesai
  - branch/time-slot/blackout/printer/payment/queue snapshot memakai owner service
- Batch 4: selesai
  - recent transactions dan recent activities dipindah ke owner read service
- Batch 5: selesai
  - package/add-on/design/user/inventory read payload dipindah ke owner service
- Batch 6: parsial
  - frontend admin sudah memakai beberapa composable existing
  - extraction tambahan root admin masih bisa dilanjutkan bila ingin mengecilkan file lebih agresif
- Batch 7: parsial
  - `moduleRegistry.js` ditambahkan untuk merapikan fetch-on-enter modul admin
- Batch 8: selesai untuk backend wrapper utama
  - wrapper read legacy di `AdminDashboardDataService` dihapus
- Batch 9: berjalan
  - dokumen refactor ini ditambahkan dan changelog diperbarui

## Residual Frontend Work

- Ekstraksi penuh state/method modul `reports`, `queue`, `bookings`, `settings`, `packages`, `add-ons`, `stock`, dan `users` dari `AdminDashboardApp.vue` ke composable dedicated masih bisa dilakukan sebagai tahap lanjutan.
- Template root admin masih memakai `v-if / v-else-if` chain untuk page switch.

## Guardrails

- Jangan ubah route name / URL endpoint tanpa kebutuhan eksplisit.
- Jangan ubah shape JSON response admin tanpa migrasi caller frontend yang jelas.
- Pertahankan validasi request di controller/Form Request yang sudah ada.
