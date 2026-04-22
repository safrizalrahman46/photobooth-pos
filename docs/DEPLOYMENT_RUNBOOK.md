# Deployment Runbook (Web + Desktop)

## A. Deploy Web (Laravel + Filament)

1. Clone project dan siapkan `.env` production.
2. Install dependency:

```bash
composer install --no-dev --optimize-autoloader
npm install
```

3. Build asset dan optimize:

```bash
npm run build
php artisan migrate --force
php artisan db:seed --force
php artisan optimize
php artisan filament:cache-components
```

4. Jalankan service web (Nginx/Apache + PHP-FPM) sesuai server.

5. Verifikasi cepat:

- landing page: `/`
- booking page: `/booking`
- queue board: `/queue-board`
- admin panel: `/admin`

## B. Deploy Desktop Windows

1. Build desktop release:

```bash
cd apps/desktop_flutter
flutter pub get
flutter build windows
```

2. Package portable release:

```powershell
./scripts/package_windows_release.ps1 -Version v1.0.0
```

3. (Opsional) Build installer:

```powershell
./scripts/build_installer.ps1 -Version v1.0.0
```

4. Distribusi ke PC kasir/owner.

## C. Post-Deploy Verification

1. Jalankan smoke test API:

```powershell
./apps/desktop_flutter/scripts/smoke_test_backend.ps1 -ApiBaseUrl http://YOUR_HOST/api/v1 -Email owner@readytopict.test -Password password
```

2. Login desktop dengan akun owner dan cashier.
3. Uji alur end-to-end:

- booking web
- check-in queue
- transaksi POS
- pembayaran
- cetak receipt

4. Isi data master minimum:

- cabang
- paket
- slot jam
- blackout date
- printer setting
