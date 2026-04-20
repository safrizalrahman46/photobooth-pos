# Ready To Pict Desktop

Desktop app Windows untuk operasional `kasir` dan `owner` yang memakai backend Laravel dari repo ini.

## Scope awal

- Login ke API Laravel via Sanctum token
- Workspace role-based untuk kasir dan owner
- Kasir: queue operasional (call next, check-in booking, walk-in), POS, dan cetak receipt
- Owner: laporan ringkas, website settings, manajemen cabang/paket, dan manajemen slot jam

## Struktur

- `lib/app` aplikasi utama
- `lib/core` config, network, dan session store
- `lib/features/auth` login desktop
- `lib/features/kasir` fondasi workspace kasir
- `lib/features/owner` fondasi workspace owner
- `lib/shared` model dan komponen bersama

## Menjalankan

```bash
flutter pub get
flutter run -d windows
```

## Build Windows

```bash
flutter build windows
```

Pastikan API Laravel aktif dan endpoint login tersedia di base URL yang dipakai saat login.
