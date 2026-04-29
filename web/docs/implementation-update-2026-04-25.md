# Dokumentasi Perubahan Implementasi (2026-04-25)

Dokumen ini merangkum perubahan implementasi terbaru lintas modul admin dan booking publik yang terjadi setelah update `2026-04-23`.

## Ringkasan Eksekutif

Fokus perubahan:

- Menegaskan alur **payment-first** sebelum verifikasi booking.
- Otomasi antrean: booking terverifikasi dapat masuk waiting list antrean.
- Penyempurnaan manajemen slot waktu (ketersediaan real-time + alasan slot tidak bisa dipilih).
- Pemisahan add-on fisik/non-fisik + dasar tracking stok.
- Dukungan upload foto contoh paket (bukan link manual) dan fallback delivery tanpa symlink.
- Penyempurnaan notifikasi sidebar (blink + badge) untuk booking pending dan queue waiting.
- Kontrol akses UI sidebar (disable modul tertentu).
- Fitur owner untuk manage user (create/update/delete) langsung dari page Users.

## Detail Perubahan Per Modul

### 1. Booking Admin: Payment-First Verification

- Verifikasi booking disyaratkan setelah pembayaran mencukupi (`remaining_amount <= 0`).
- Tombol Verify di detail booking mengikuti state:
  - jika belum lunas, flow mengarahkan ke konfirmasi pembayaran.
  - jika sudah lunas, Verify langsung melanjutkan status booking.
- Ditambahkan aksi **Decline** di detail booking untuk kasus bukti pembayaran tidak tersedia (status menjadi cancelled).
- Bukti pembayaran (transfer proof) ditampilkan di modal detail booking, termasuk metadata waktu upload.
- Payment modal mendukung tipe pembayaran:
  - `Full Payment`
  - `DP 50%` (nominal otomatis menyesuaikan target DP).

Backend terkait:

- `app/Services/AdminBookingManagementService.php`
- `app/Http/Controllers/Web/AdminBookingController.php`
- `app/Http/Requests/AdminDeclineBookingRequest.php`
- `app/Services/AdminDashboardDataService.php`

Frontend terkait:

- `resources/js/admin/pages/BookingsPage.vue`

### 2. Queue Management: Otomasi dan Transisi Status

- Booking yang sudah memenuhi syarat verifikasi dapat di-auto check-in ke queue (dengan guard tanggal dan status).
- Ticket queue mendukung transisi status berurutan yang jelas pada UI.
- Saat skip session, ticket tetap dipertahankan di siklus antrean sesuai status flow.
- Label tombol status berikutnya diperbaiki agar tidak muncul `undefined`.
- CTA antrean diperjelas (panggil antrean berikutnya, naikkan status, dsb).

Backend terkait:

- `app/Services/QueueService.php`
- `app/Services/AdminQueuePageService.php`
- `app/Services/AdminBookingManagementService.php`

Frontend terkait:

- `resources/js/admin/pages/QueuePage.vue`

### 3. Booking Publik: Time Slot Dinamis dan Alasan Unavailable

- Slot waktu publik mengikuti data real time dari backend.
- Slot yang sudah lewat jam lokal otomatis tidak bisa dipilih.
- Alasan slot unavailable ditampilkan eksplisit:
  - `Lewat jam`
  - `Durasi paket tidak muat`
  - `Penuh`
- Validasi backend tetap memastikan slot yang dipilih benar-benar tersedia saat submit.

Backend terkait:

- `app/Services/SlotService.php`
- `app/Http/Controllers/Web/BookingController.php`

Frontend terkait:

- `resources/js/booking/BookingApp.vue`

### 4. Add-ons: Inventori + Kategori Fisik/Non-Fisik

- Add-on dipisah menjadi:
  - `Physical`
  - `Non-physical`
- Ditambah kolom inventori untuk add-on fisik:
  - `available_stock`
  - `low_stock_threshold`
- UI Add-ons menampilkan ringkasan per tipe dan indikator stok dasar.
- Data add-on diperluas untuk kebutuhan reporting/admin analytics.

Backend terkait:

- `app/Models/AddOn.php`
- `app/Services/AdminAddOnService.php`
- `app/Services/AdminDashboardDataService.php`
- `app/Http/Requests/AdminStoreAddOnRequest.php`
- `app/Http/Requests/AdminUpdateAddOnRequest.php`
- `database/migrations/2026_04_24_120000_add_is_physical_to_add_ons_table.php`
- `database/migrations/2026_04_24_140000_add_inventory_columns_to_add_ons_table.php`
- `database/seeders/AddOnSeeder.php`

Frontend terkait:

- `resources/js/admin/pages/AddOnsPage.vue`

### 5. Packages: Upload Foto Contoh Hasil (Bukan Link)

- Form Add/Edit Package sekarang mendukung upload multi file image.
- Pada edit package, foto existing bisa di-keep/hapus per item.
- Payload package admin kini mendukung field:
  - `sample_photos_files[]`
  - `sample_photos_keep[]`
  - `sample_photos_keep_present`
- Foto disimpan pada `storage/app/public/package-samples`.
- URL sample photo dinormalisasi agar kompatibel dengan data lama.

#### Fallback Delivery tanpa `public/storage` symlink

Untuk lingkungan Windows yang gagal membuat symlink (`storage:link` access denied), ditambahkan endpoint file streaming:

- `GET /media/package-samples/{path}`

Endpoint ini melayani gambar dari disk `public` tanpa symlink filesystem.

Backend terkait:

- `app/Services/AdminPackageService.php`
- `app/Models/Package.php`
- `app/Http/Resources/PackageResource.php`
- `app/Services/AdminDashboardDataService.php`
- `app/Http/Controllers/Web/BookingController.php`
- `app/Http/Controllers/Web/PackageSamplePhotoController.php`
- `routes/web.php`
- `app/Http/Requests/AdminStorePackageRequest.php`
- `app/Http/Requests/AdminUpdatePackageRequest.php`

Frontend terkait:

- `resources/js/admin/pages/PackagesPage.vue`
- `resources/js/admin/AdminDashboardApp.vue`
- `resources/js/booking/BookingApp.vue`

Migrasi terkait:

- `database/migrations/2026_04_25_120000_add_sample_photos_to_packages_table.php`
- `database/migrations/2026_04_25_130000_normalize_sample_photos_values_in_packages_table.php`

### 6. Sidebar Notifikasi + Disable Item

- Badge dan blink sidebar untuk:
  - `Bookings` (jumlah pending booking)
  - `Queue` (jumlah verified waiting queue)
- Kecepatan blink mengikuti jumlah item (semakin banyak, semakin cepat).
- Item sidebar berikut di-disable (non-clickable):
  - `Designs`
  - `Settings`
  - `App Settings`

Frontend terkait:

- `resources/js/admin/AdminDashboardApp.vue`
- `resources/js/admin/components/AdminSidebar.vue`

### 7. Users Management (Owner)

- Owner sekarang dapat melakukan manajemen user dari page Users:
  - create user
  - edit user
  - ubah role/status aktif
  - delete user
- Guard backend:
  - hanya owner yang bisa aksi manage user.
  - owner terakhir tidak boleh dihapus/diturunkan/nonaktif.
  - user tidak bisa menonaktifkan atau menghapus akun sendiri.

Backend terkait:

- `app/Http/Controllers/Web/AdminUserController.php`
- `app/Services/AdminUserService.php`
- `app/Http/Requests/AdminUpdateUserRequest.php`
- `routes/web.php`
- `app/Http/Controllers/Web/AdminDashboardController.php` (bootstrap `userBaseUrl`)

Frontend terkait:

- `resources/js/admin/pages/UsersPage.vue`
- `resources/js/admin/AdminDashboardApp.vue`

## Endpoint Tambahan/Perubahan Penting

### Users

- `PUT /admin/users/{user}`
- `DELETE /admin/users/{user}`

### Package sample media

- `GET /media/package-samples/{path}`

## Migrasi yang Harus Dijalankan

Jika belum dijalankan, pastikan:

1. `php artisan migrate`
2. `php artisan optimize:clear`

Opsional untuk environment non-Windows:

3. `php artisan storage:link`

Catatan: fallback route `/media/package-samples/*` tetap bekerja walaupun symlink gagal.

## Checklist QA yang Direkomendasikan

1. Booking unpaid tidak bisa langsung verify tanpa proses pembayaran.
2. Booking terverifikasi (hari yang sama) muncul di opsi queue/waiting list.
3. Slot publik menampilkan alasan unavailable (`Lewat jam`, `Durasi paket tidak muat`, `Penuh`).
4. Add-on physical/non-physical tampil terpisah dan data stok tersimpan.
5. Upload foto package sukses, preview tampil di admin dan booking.
6. Sidebar badge/blink untuk Booking + Queue bekerja sesuai jumlah item.
7. Sidebar item `Designs/Settings/App Settings` tidak bisa diklik.
8. Owner bisa edit/hapus user, tetapi owner terakhir dan akun sendiri terlindungi.

