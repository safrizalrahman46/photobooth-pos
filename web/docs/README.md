# Dokumentasi Proyek

Folder ini menyimpan dokumentasi konfigurasi dan perubahan implementasi proyek.

## Aturan Dokumentasi

- Semua perubahan konfigurasi harus dicatat di folder `docs`.
- Semua perubahan fitur penting harus ditambahkan ke `docs/change-log.md`.
- Jika ada endpoint baru, update juga dokumentasi terkait di `docs/admin-dashboard-config.md` atau dokumen modul lain.

## Daftar Dokumen

- `configuration-overview.md`: ringkasan konfigurasi stack, environment, build, dan runtime.
- `booking-flow.md`: alur booking publik termasuk page data pemesan sebelum booking sesi foto.
- `admin-dashboard-config.md`: konfigurasi dashboard admin (Filament + Vue + endpoint data).
- `change-log.md`: catatan perubahan yang sudah diimplementasikan.
- `implementation-update-2026-04-23.md`: dokumentasi lengkap perubahan terbaru terkait dynamic frontend data dan konfigurasi UI berbasis backend.
- `implementation-update-2026-04-25.md`: dokumentasi lengkap perubahan terbaru terkait booking/queue/payment flow, add-on inventory, package sample upload, sidebar control, dan owner user management.
- `inventory-stock-flow-2026-04-30.md`: dokumentasi pemisahan add-on vs inventory item, mapping konsumsi, auto deduction saat verifikasi booking, dan endpoint stock baru.
- `desktop-pos-api-integration-2026-05-01.md`: dokumentasi integrasi Flutter desktop POS dengan API Laravel, checkout walk-in, verifikasi booking, dan print struk.
- `admin-history-dashboard-reports-2026-05-02.md`: dokumentasi perubahan dashboard admin minimal, History Perubahan, report cashier daily, export Excel, dan badge queue total waiting.
- `admin-refactor-roadmap.md`: status boundary refactor backend/frontend admin dan owner service per modul.
- `public-booking-branch-analytics-update-2026-05-02.md`: dokumentasi flow booking customer-first, QR payment per branch, realtime bookings admin, dan mode analytics reports.
- `time-slot-stage1-redesign-2026-05-02.md`: dokumentasi Tahap 1 redesign Time Slots dan konsistensi kapasitas booking paralel.
