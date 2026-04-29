# Admin Dashboard Configuration

Dokumen ini merangkum arsitektur admin terbaru berbasis Vue (`AdminDashboardApp.vue`) dengan fallback Filament melalui env toggle.

## Control Plane

- Driver UI admin dikontrol oleh config `config/admin_ui.php`.
- Env yang dipakai:
  - `ADMIN_UI_DRIVER=vue|filament`
  - `ADMIN_UI_BLOCK_FILAMENT_ROUTES=true|false`
  - `ADMIN_UI_LEGACY_REDIRECTS=true|false`
- Saat `ADMIN_UI_DRIVER=filament`, `AdminPanelProvider` diregistrasi secara conditional dari `AppServiceProvider`.
- Saat `ADMIN_UI_DRIVER=vue` dan `ADMIN_UI_BLOCK_FILAMENT_ROUTES=true`, middleware global web `BlockFilamentRoutesWhenDisabled` mengembalikan `404` untuk path `filament/*`.

## Runtime Admin Vue

- Shell admin: route web `/admin` (controller `AdminDashboardController`).
- Frontend shell: `resources/js/admin/AdminDashboardApp.vue`.
- Halaman Vue dipisah per page di `resources/js/admin/pages/*`.
- Logic state + API modul parity dipisah ke composable:
  - `resources/js/admin/composables/useBranchesModule.js`
  - `resources/js/admin/composables/useTimeSlotsModule.js`
  - `resources/js/admin/composables/useBlackoutDatesModule.js`
  - `resources/js/admin/composables/usePaymentsModule.js`
  - `resources/js/admin/composables/usePrinterSettingsModule.js`
  - `resources/js/admin/composables/useAppSettingsModule.js`
- Bootstrap props dikirim dari `AdminDashboardController`, termasuk URL endpoint modul dan initial payload.
- Bootstrap juga mengirim `uiConfig` (dari App Setting group `ui.admin`) untuk label/menu/sidebar/topbar agar frontend admin tidak hardcode label di Vue.
- Profil sidebar (`currentUser`) dan branding (`brand`) juga dikirim dari backend agar tidak ada data contoh statis di komponen.
- Legacy redirect kompatibilitas:
  - `/panel/* -> /admin` (jika `ADMIN_UI_LEGACY_REDIRECTS=true`)
  - `/admin/admin-dashboard -> /admin` (jika `ADMIN_UI_LEGACY_REDIRECTS=true`)

## Bootstrap Endpoint Map (Parity Modules)

- Branches:
  - `branchesDataUrl`
  - `branchStoreUrl`
  - `branchBaseUrl`
- Time slots:
  - `timeSlotsDataUrl`
  - `timeSlotStoreUrl`
  - `timeSlotBaseUrl`
  - `timeSlotGenerateUrl`
  - `timeSlotBulkBookableUrl`
- Blackout dates:
  - `blackoutDatesDataUrl`
  - `blackoutDateStoreUrl`
  - `blackoutDateBaseUrl`
- Payments:
  - `paymentsDataUrl`
  - `paymentsStoreUrlBase`
- Printer settings:
  - `printerSettingsDataUrl`
  - `printerSettingStoreUrl`
  - `printerSettingBaseUrl`
- App settings:
  - `appSettingsDataUrl`
  - `appSettingBaseUrl`
  - Group yang tersedia: `general`, `booking`, `payment`, `ui`

## Route Matrix (Vue Mode)

- Auth:
  - `GET /admin/login` (`admin.login`)
  - `POST /admin/login` (`admin.login.attempt`)
  - `POST /admin/logout` (`admin.logout`)
- Shell pages:
  - `/admin`, `/admin/packages`, `/admin/add-ons`, `/admin/design-catalogs`, `/admin/users`
  - `/admin/bookings`, `/admin/queue-tickets`, `/admin/transactions`, `/admin/reports`, `/admin/activity-logs`
  - `/admin/settings`, `/admin/branches`, `/admin/time-slots`, `/admin/blackout-dates`
  - `/admin/payments`, `/admin/printer-settings`, `/admin/app-settings`
- Data/action endpoints utama:
  - Dashboard/report: `admin.dashboard.data`, `admin.dashboard.report`
  - Existing admin modules: `admin.packages.*`, `admin.add-ons.*`, `admin.designs.*`, `admin.users.*`, `admin.queue.*`, `admin.bookings.*`, `admin.settings.*`
  - Added parity modules:
    - `admin.branches.data`, `admin.branches.store`
    - `admin.time-slots.data`, `admin.time-slots.store`, `admin.time-slots.bulk-bookable`, `admin.time-slots.generate`
    - `admin.blackout-dates.data`, `admin.blackout-dates.store`
    - `admin.printer-settings.data`, `admin.printer-settings.store`, `admin.printer-settings.set-default`
    - `admin.payments.data`, `admin.payments.store`
    - `admin.app-settings.data`, `admin.app-settings.update`

## Rollback

1. Set `ADMIN_UI_DRIVER=filament`.
2. Jalankan `php artisan optimize:clear`.
3. Verifikasi `php artisan route:list` menampilkan route panel Filament aktif untuk `/admin`.

## Validation Checklist

1. `php artisan route:list` menampilkan route `admin.*` baru.
2. Dengan `ADMIN_UI_DRIVER=vue`, akses `filament/*` diblokir `404` (jika block aktif).
3. `npm.cmd run build` sukses setelah update module Vue.
4. Modul `/admin/branches`, `/admin/time-slots`, `/admin/blackout-dates`, `/admin/payments`, `/admin/printer-settings`, `/admin/app-settings` menampilkan page tanpa blank state akibat reference error.
