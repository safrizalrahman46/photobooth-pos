# Ready To Pict Desktop

Desktop app Windows untuk operasional `kasir` dan `owner` yang memakai backend Laravel dari repo ini.

## Scope awal

- Login ke API Laravel via Sanctum token
- Workspace role-based untuk kasir dan owner
- Kasir: queue operasional (call next, check-in booking, walk-in), POS, dan cetak receipt
- Owner: laporan ringkas, website settings, manajemen cabang/paket, dan manajemen slot jam (single, bulk generate, bulk bookable)

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

## Packaging release (opsional)

PowerShell:

```powershell
./scripts/package_windows_release.ps1 -Version v1.0.0
```

Output package ada di folder `apps/desktop_flutter/release/...`.

## Build installer (Inno Setup)

```powershell
./scripts/build_installer.ps1 -Version v1.0.0
```

Butuh Inno Setup Compiler (`ISCC.exe`) ter-install di Windows.

## Smoke test backend dari desktop

```powershell
./scripts/smoke_test_backend.ps1 -ApiBaseUrl http://127.0.0.1:8000/api/v1 -Email owner@readytopict.test -Password password
```

Pastikan API Laravel aktif dan endpoint login tersedia di base URL yang dipakai saat login.
