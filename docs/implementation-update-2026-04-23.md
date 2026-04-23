# Dokumentasi Perubahan Implementasi (2026-04-23)

Dokumen ini merangkum seluruh perubahan implementasi terbaru yang berfokus pada penghilangan data statis di frontend, pemindahan konfigurasi UI ke backend, dan penguatan arsitektur modular admin/public booking.

## Ruang Lingkup

Perubahan pada dokumen ini mencakup:

- Backend controller/service untuk bootstrap data dinamis.
- Frontend Vue admin agar label/menu/tab berasal dari payload backend.
- Frontend Vue booking/payment agar step, navigation, add-ons, dan preview tidak statis.
- Halaman App Settings agar konfigurasi UI dapat diedit melalui panel admin.
- Update dokumentasi teknis pendukung.

## Ringkasan Hasil

- Data bisnis di frontend booking sekarang diambil dari backend (DB/payload), bukan hardcoded.
- Label/menu utama admin sekarang dapat dikonfigurasi dari App Settings group `ui`.
- Sidebar admin tidak lagi menampilkan profil contoh statis.
- Navbar booking/payment dan mobile booking steps tidak lagi didefinisikan statis di komponen Vue.
- Group `ui` telah ditambahkan ke alur app settings agar perubahan label/menu dapat dikelola owner/admin.

## Detail Perubahan Per Layer

### Backend

1. App settings menambahkan group `ui` dengan default schema:
- `ui.admin` untuk menu/group/topbar/filter/settings tab admin.
- `ui.booking` untuk `steps` dan `navigation` booking public.

File:
- `app/Services/AppSettingService.php`

2. Group `ui` diizinkan untuk update dari endpoint App Settings.

File:
- `app/Services/AdminAppSettingService.php`

3. Bootstrap admin dashboard menambahkan:
- `uiConfig` (berasal dari `siteSettings.ui.admin`)
- `initialAppSettingsGroups` (termasuk group `ui`)
- `brand` dan `currentUser` untuk sidebar/top-level identity.

File:
- `app/Http/Controllers/Web/AdminDashboardController.php`

4. Bootstrap booking create/payment membaca konfigurasi `ui.booking` dan membentuk navigation secara dinamis (via `route` atau `href`).

File:
- `resources/views/web/booking-create.blade.php`
- `resources/views/web/booking-payment.blade.php`

5. Booking create menyiapkan add-ons aktif dari database agar frontend booking tidak memakai katalog statis.

File:
- `app/Http/Controllers/Web/BookingController.php`

### Frontend Admin (Vue)

1. `AdminDashboardApp.vue` sekarang membaca `uiConfig` untuk:
- `nav_groups`
- `nav_items`
- `topbar_meta`
- `booking_filter_tabs`
- `settings_tabs`

2. Mapping icon menu dilakukan melalui `icon key -> lucide component` agar item menu tetap configurable tanpa hardcode label/menu.

3. Active module detection sekarang berbasis daftar nav dinamis dari payload.

4. Fallback queue code sintetis (`Q001` style) dihapus agar tidak muncul data pseudo saat payload kosong.

File:
- `resources/js/admin/AdminDashboardApp.vue`

5. Sidebar admin memakai data backend:
- `brandName`
- `dashboardLabel`
- `currentUser` (nama, role, initials)

File:
- `resources/js/admin/components/AdminSidebar.vue`

6. App Settings module menambahkan editor JSON untuk group `ui`.

File:
- `resources/js/admin/pages/AppSettingsPage.vue`
- `resources/js/admin/composables/useAppSettingsModule.js`

### Frontend Booking/Public (Vue)

1. Booking app:
- Add-ons diambil dari props backend (`addOns`) dan difilter berdasar paket.
- Gallery preview menggunakan data desain aktif (`preview_url`) dari backend.
- Mobile `stepLabels` diambil dari `props.ui.steps`.
- Navigation navbar menggunakan props dinamis.

File:
- `resources/js/booking/BookingApp.vue`

2. Public navbar:
- Tidak lagi mendefinisikan link `Book/Admin/Queue` secara statis.
- Link dibentuk dari `props.navigation`.

File:
- `resources/js/booking/PublicBookingNavbar.vue`

3. Payment app:
- Mendukung props `navigation` agar navbar konsisten dinamis.
- `onlinePaymentEnabled` mengikuti `paymentSettings` payload.

File:
- `resources/js/booking/PaymentApp.vue`

## Kontrak Konfigurasi UI (App Settings Group `ui`)

Contoh struktur minimal:

```json
{
  "admin": {
    "nav_groups": [
      { "key": "overview", "label": "Overview" },
      { "key": "management", "label": "Management" }
    ],
    "nav_items": [
      { "id": "dashboard", "label": "Dashboard", "icon": "dashboard", "href": "/admin", "group": "overview" },
      { "id": "packages", "label": "Packages", "icon": "package", "href": "/admin/packages", "group": "management" }
    ],
    "topbar_meta": {
      "dashboard": { "title": "Dashboard", "subtitle": "Business overview and key metrics" }
    },
    "booking_filter_tabs": [
      { "key": "all", "label": "All" },
      { "key": "pending", "label": "Pending" }
    ],
    "settings_tabs": [
      { "id": "branch", "label": "Branch Setting" },
      { "id": "hours", "label": "Operating Hours" }
    ]
  },
  "booking": {
    "steps": ["Paket", "Tanggal", "Waktu", "Add-on"],
    "navigation": [
      { "key": "book", "label": "Book", "route": "booking.create" },
      { "key": "admin", "label": "Admin", "route": "admin.login" },
      { "key": "queue", "label": "Queue", "route": "queue.board" }
    ]
  }
}
```

Catatan:
- Untuk `navigation`, `route` diprioritaskan; jika invalid, sistem fallback ke `href` lalu `#`.
- `icon` admin mendukung key seperti: `dashboard`, `package`, `palette`, `users`, `calendar`, `list`, `receipt`, `chart`, `activity`, `settings`.

## Dampak dan Kompatibilitas

- Perubahan bersifat backward-compatible karena default schema `ui` disediakan oleh backend.
- Jika nilai `ui` kosong, frontend tetap bekerja dengan fallback aman dari payload default.
- Perubahan ini mengurangi risiko blank page akibat mismatch label/menu hardcoded vs route aktual.

## Validasi Teknis yang Sudah Dijalankan

- Lint PHP:
  - `php -l app/Services/AppSettingService.php`
  - `php -l app/Services/AdminAppSettingService.php`
  - `php -l app/Http/Controllers/Web/AdminDashboardController.php`
  - `php -l resources/views/web/booking-create.blade.php`
  - `php -l resources/views/web/booking-payment.blade.php`
- Build frontend:
  - `npm run build` (berhasil).

## Daftar File yang Berubah

- `app/Http/Controllers/Web/AdminDashboardController.php`
- `app/Http/Controllers/Web/BookingController.php`
- `app/Services/AdminAppSettingService.php`
- `app/Services/AppSettingService.php`
- `resources/js/admin/AdminDashboardApp.vue`
- `resources/js/admin/components/AdminSidebar.vue`
- `resources/js/admin/composables/useAppSettingsModule.js`
- `resources/js/admin/pages/AppSettingsPage.vue`
- `resources/js/booking/BookingApp.vue`
- `resources/js/booking/PaymentApp.vue`
- `resources/js/booking/PublicBookingNavbar.vue`
- `resources/views/web/booking-create.blade.php`
- `resources/views/web/booking-payment.blade.php`
- `docs/admin-dashboard-config.md`
- `docs/configuration-overview.md`

