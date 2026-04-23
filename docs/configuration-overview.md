# Configuration Overview

## Tech Stack

- Backend: Laravel 12
- Admin Panel Runtime: Vue admin shell (`AdminDashboardApp.vue`) + Laravel web controllers/services
- Admin Fallback: Filament 4 + Livewire 3 (toggleable via env)
- Frontend: Vue 3 + Vite 7
- Styling: Tailwind CSS 4
- Database target: PostgreSQL (project requirement)
- Runtime PHP: >= 8.2

## Backend Dependencies (utama)

Dari `composer.json`:

- `laravel/framework:^12.0`
- `filament/filament:^4`
- `livewire/livewire:^3`
- `spatie/laravel-permission:^6.25`
- `darkaonline/l5-swagger:^11.0`
- `rupadana/filament-api-service:^4.0`

## Frontend Dependencies (utama)

Dari `package.json`:

- `vue:^3.5.32`
- `vite:^7.0.7`
- `@vitejs/plugin-vue:^6.0.5`
- `tailwindcss:^4.0.0`
- `@tailwindcss/vite:^4.0.0`
- `laravel-vite-plugin:^2.0.0`

## Build & Development Commands

### Composer scripts

- `composer run setup`: install dependency + inisialisasi `.env` + migrate + build frontend.
- `composer run dev`: jalankan server Laravel, queue listener, pail log, dan Vite secara paralel.
- `composer run test`: clear config lalu jalankan test.

### NPM scripts

- `npm run dev`: jalankan Vite dev server.
- `npm run build`: build asset production ke folder `public/build`.

## Vite Configuration

File: `vite.config.js`

- Input asset: `resources/css/app.css` dan `resources/js/app.js`.
- Plugin aktif: Laravel plugin, Vue plugin, Tailwind plugin.
- Watch ignore: `storage/framework/views/**` untuk menghindari loop rebuild dari compiled Blade.

## Frontend Admin Structure

- Entry admin Vue:
  - `resources/js/admin/AdminDashboardApp.vue`
- Admin pages (UI per modul):
  - `resources/js/admin/pages/*`
- Admin composables (state + API layer modul parity):
  - `resources/js/admin/composables/useBranchesModule.js`
  - `resources/js/admin/composables/useTimeSlotsModule.js`
  - `resources/js/admin/composables/useBlackoutDatesModule.js`
  - `resources/js/admin/composables/usePaymentsModule.js`
  - `resources/js/admin/composables/usePrinterSettingsModule.js`
  - `resources/js/admin/composables/useAppSettingsModule.js`
- Tujuan modularisasi:
  - `AdminDashboardApp.vue` fokus sebagai orchestrator layout/routing modul.
  - Logic CRUD/fetch tiap modul dipisah agar maintenance lebih mudah dan risiko konflik perubahan lebih kecil.

## CSS Pipeline

File: `resources/css/app.css`

- Menggunakan `@import 'tailwindcss'`.
- Mengimpor style Filament package (support/actions/forms/infolists/notifications/schemas/tables/widgets).
- Source scan Tailwind:
  - `resources/**/*.blade.php`
  - `resources/**/*.js`
  - `resources/**/*.vue`
  - `storage/framework/views/*.php`
  - view pagination Laravel.

## Environment Configuration Baseline

Dari `.env.example`:

- APP:
  - `APP_ENV`, `APP_DEBUG`, `APP_URL`
  - `ADMIN_UI_DRIVER` (`vue` atau `filament`)
  - `ADMIN_UI_BLOCK_FILAMENT_ROUTES` (`true|false`)
  - `ADMIN_UI_LEGACY_REDIRECTS` (`true|false`)
- DB:
  - default contoh `sqlite`, tapi untuk project ini gunakan PostgreSQL di `.env`
  - set minimal: `DB_CONNECTION=pgsql`, `DB_HOST`, `DB_PORT`, `DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD`
- Session/Queue/Cache:
  - default contoh: `SESSION_DRIVER=database`, `QUEUE_CONNECTION=database`, `CACHE_STORE=database`
- Mail:
  - default `MAIL_MAILER=log` (ubah untuk SMTP jika dibutuhkan)

## Cache & Rebuild Rekomendasi Setelah Perubahan Konfigurasi

Jalankan:

1. `php artisan optimize:clear`
2. `npm run build`
