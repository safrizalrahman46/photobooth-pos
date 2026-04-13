# Change Log

## 2026-04-14

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
