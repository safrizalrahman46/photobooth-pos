# Go-Live Checklist

## 1) Backend

- [ ] `.env` production sudah benar (`APP_ENV=production`, `APP_DEBUG=false`)
- [ ] database production sudah migrate
- [ ] akun owner dan kasir sudah tersedia
- [ ] permission role sudah benar (owner, cashier)
- [ ] queue, booking, transaction, payment endpoint bisa diakses

## 2) Web

- [ ] `npm run build` berhasil
- [ ] admin login bisa diakses sesuai driver aktif (`ADMIN_UI_DRIVER=vue|filament`)
- [ ] resource master data terisi (cabang, paket, slot jam)
- [ ] blackout date dan printer setting terisi sesuai cabang
- [ ] modul admin parity Vue bisa diakses normal:
  - [ ] `/admin/branches`
  - [ ] `/admin/time-slots`
  - [ ] `/admin/blackout-dates`
  - [ ] `/admin/payments`
  - [ ] `/admin/printer-settings`
  - [ ] `/admin/app-settings`
- [ ] halaman booking publik bisa create booking
- [ ] halaman queue board tampil normal

## 3) Desktop Windows

- [ ] `flutter build windows` berhasil
- [ ] `desktop_flutter.exe` bisa dijalankan di PC kasir/owner
- [ ] (opsional) installer desktop berhasil dibuild via `apps/desktop_flutter/scripts/build_installer.ps1`
- [ ] login ke API production berhasil
- [ ] kasir: check-in booking, walk-in, call next, POS, payment berjalan
- [ ] kasir: print receipt berjalan
- [ ] owner: report, setting web, manajemen slot/cabang/paket berjalan

## 4) Smoke Test API

- [ ] jalankan smoke test backend: `apps/desktop_flutter/scripts/smoke_test_backend.ps1`
- [ ] endpoint public dan endpoint auth utama merespons sukses

## 5) Operasional

- [ ] data cabang utama dan jam operasional sudah dipastikan
- [ ] slot jam per cabang sudah digenerate
- [ ] uji transaksi end-to-end (booking -> queue -> POS -> payment -> receipt)
- [ ] backup database terjadwal aktif

## 6) Opsional Midtrans

- [ ] akun Midtrans aktif
- [ ] `MIDTRANS_SERVER_KEY` dan `MIDTRANS_CLIENT_KEY` terisi
- [ ] webhook Midtrans mengarah ke endpoint API
- [ ] uji pembayaran sandbox berhasil
