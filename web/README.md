# Ready To Pict - Photobooth POS

Project ini berisi:

- Website publik booking + queue board
- Admin panel (Filament)
- API backend (Laravel Sanctum)
- Desktop app Windows (Flutter) untuk kasir + owner

## Tech Stack

- Backend: Laravel 12, PHP 8.2+
- Admin: Filament 4
- Frontend web: Blade + Vue + Vite
- Desktop: Flutter (Windows)

## Fitur Utama

- Booking online dengan slot jam per cabang
- Queue operasional (check-in booking, walk-in, call next, transition)
- POS transaksi + pembayaran
- Laporan ringkas owner
- Manajemen cabang, paket, slot jam, blackout date, printer setting
- Desktop kasir/owner terhubung ke API Laravel yang sama

## Setup Backend & Web

1) Install dependency

```bash
composer install
npm install
```

2) Setup environment

```bash
cp .env.example .env
php artisan key:generate
```

3) Setup database

```bash
php artisan migrate --seed
```

4) Jalankan aplikasi

```bash
php artisan serve
npm run dev
```

## Build Production Web

```bash
npm run build
php artisan optimize
```

## Menjalankan Desktop App (Windows)

Masuk ke folder desktop:

```bash
cd apps/desktop_flutter
flutter pub get
flutter run -d windows
```

Build release:

```bash
flutter build windows
```

Output executable:

`apps/desktop_flutter/build/windows/x64/runner/Release/desktop_flutter.exe`

## Dokumen Deployment

- Checklist go-live: `docs/GO_LIVE_CHECKLIST.md`
- Runbook deploy lengkap: `docs/DEPLOYMENT_RUNBOOK.md`
- Desktop packaging + installer script: `apps/desktop_flutter/scripts/`

## Testing

- Backend:

```bash
php artisan test
```

- Desktop:

```bash
cd apps/desktop_flutter
flutter analyze
flutter test
```

## Catatan Midtrans

Integrasi Midtrans sudah disiapkan di kode. Untuk aktivasi penuh, isi kredensial Midtrans di `.env` sesuai environment yang dipakai.
