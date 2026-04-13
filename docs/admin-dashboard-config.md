# Admin Dashboard Configuration

Dokumen ini menjelaskan konfigurasi halaman dashboard admin kustom berbasis Filament + Vue.

## Arsitektur

- Shell admin: Filament panel (`/admin`)
- Halaman dashboard: Filament Page kustom
- Rendering konten dashboard: Vue component di mount node Blade
- Data tabel: server-driven (search, status filter, pagination) dari endpoint JSON internal

## File Utama

- `app/Providers/Filament/AdminPanelProvider.php`
- `app/Filament/Pages/AdminDashboard.php`
- `resources/views/filament/pages/admin-dashboard.blade.php`
- `resources/js/admin/AdminDashboardApp.vue`
- `resources/js/app.js`
- `app/Services/AdminDashboardDataService.php`
- `app/Http/Controllers/Web/AdminDashboardDataController.php`
- `routes/web.php`

## Filament Panel Registration

Pada `AdminPanelProvider`:

- Panel ID: `admin`
- Path: `/admin`
- Halaman dashboard kustom didaftarkan sebagai page.
- Asset Vite panel disuntik melalui render hook `HEAD_END`:
  - `resources/css/app.css`
  - `resources/js/app.js`

Ini memastikan Tailwind utility + logic mount Vue tersedia saat halaman admin dibuka.

## Dashboard Page Bootstrap

Pada `AdminDashboard` page:

- Route path page: `/` dalam context panel admin (URL final: `/admin/admin-dashboard`).
- Method `bootstrapData()` mengirim:
  - `initialStats`
  - `initialRows`
  - `initialPagination`
  - `dataUrl` ke endpoint `admin.dashboard.data`

## Blade Mount Point

View dashboard menggunakan:

- `<div id="admin-dashboard-app"></div>` sebagai mount node
- `<script type="application/json" id="admin-dashboard-app-props">` untuk bootstrap props

## Vue Mount Lifecycle

Pada `resources/js/app.js`:

- Tersedia fungsi mount untuk:
  - booking app
  - payment app
  - admin dashboard app
- Ada registry `mountedApps` untuk mencegah duplikasi mount.
- App akan:
  - unmount saat node tidak ada
  - remount saat node tersedia lagi
- Re-mount dipicu pada:
  - `DOMContentLoaded`
  - `livewire:navigated` (penting untuk navigasi Filament yang SPA-like)

## Data Endpoint

Route:

- `GET /admin/dashboard-data`
- Name: `admin.dashboard.data`
- Middleware: `FilamentAuthenticate`

Controller validasi query:

- `search` (nullable string max 120)
- `status` (`all|pending|booked|used|expired`)
- `per_page` (1..100)

Response JSON:

- `data.rows`
- `data.pagination` (`current_page`, `per_page`, `total`, `last_page`)

## Service Mapping

`AdminDashboardDataService` bertugas:

- Hitung stat card dashboard.
- Query booking + relasi package dan transaction items.
- Terapkan filter status/search.
- Konversi row ke format UI dashboard.
- Mapping status domain ke status UI:
  - `pending -> pending`
  - `done -> used`
  - `cancelled -> expired`
  - lainnya -> `booked`

## Troubleshooting Cepat

Jika dashboard tampil rusak/kosong setelah perubahan config:

1. `php artisan optimize:clear`
2. `npm run build`
3. Hard refresh browser (Ctrl+F5)
