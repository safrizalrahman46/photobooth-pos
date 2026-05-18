# Website Optimization Process

Dokumen ini merangkum proses optimasi website Ready To Pict POS untuk fase production. Fokus utama adalah mempercepat login, memperingan dashboard admin, memperbaiki pengalaman user saat error, dan menjaga performa backend saat data bertambah.

## Tujuan

- Mempercepat halaman login dan first load dashboard admin.
- Mengurangi ukuran bundle JavaScript yang dimuat di halaman yang tidak membutuhkan dashboard.
- Mengubah dashboard admin agar data besar dimuat saat module/sidebar dibuka, bukan semua saat login.
- Mengurangi query berat dan N+1 query di backend.
- Menampilkan pesan error production yang mudah dipahami user.
- Menyiapkan konfigurasi production yang stabil, aman, dan mudah dipantau.

## Temuan Saat Ini

- Halaman login memakai layout `x-layouts.admin`.
- Layout admin selalu memuat `resources/css/app.css` dan `resources/js/app.js`.
- `resources/js/app.js` meng-import semua app Vue secara static, termasuk `AdminDashboardApp`.
- Dashboard admin meng-import banyak page secara static, seperti packages, bookings, stock, users, payments, time slots, dan lainnya.
- Setelah login, dashboard memuat banyak data awal melalui `AdminDashboardDataService::snapshot()`.
- Initial dashboard payload berisi data dari banyak module sekaligus: stats, report, queue, packages, add-ons, stock, designs, users, branches, time slots, blackout dates, printer settings, payments, referrals, inventory, dan booking rows.
- Error network seperti `Failed to fetch` belum diklasifikasikan menjadi pesan yang ramah user.
- Konfigurasi exception Laravel masih default dan belum memberi format JSON production yang konsisten untuk request AJAX/API.

## Prioritas Optimasi

1. Pisahkan bundle login dan dashboard admin.
2. Terapkan dynamic import untuk app Vue dan page admin.
3. Kurangi data bootstrap awal dashboard.
4. Terapkan lazy-load data per module sidebar.
5. Tambahkan pagination/filter untuk data besar.
6. Optimasi query dan index database.
7. Optimasi gambar upload dan media delivery.
8. Perbaiki error handling production.
9. Aktifkan cache dan konfigurasi production Laravel/Vite.
10. Tambahkan monitoring performa dan error.

## Tahap 1 - Optimasi Login Page

Masalah:

- Halaman login ikut memuat bundle utama `app.js`.
- `app.js` membawa kode dashboard admin walaupun login page tidak memakai dashboard.

Target:

- Login page hanya memuat CSS dan JavaScript minimal.
- Kode dashboard tidak ikut di-download sebelum user berhasil login.

Langkah implementasi:

- Buat entrypoint khusus login, misalnya `resources/js/admin-login.js`, jika login membutuhkan script kecil.
- Atau jangan muat JavaScript sama sekali di login jika tidak ada interaksi khusus.
- Pisahkan layout login dari layout admin dashboard.
- Ubah `admin-login.blade.php` agar memakai layout yang tidak memuat `resources/js/app.js`.

Checklist:

- Login page tetap tampil normal tanpa bundle dashboard.
- Form login tetap bisa submit.
- Error validasi login tetap tampil.
- Network tab tidak lagi memuat chunk dashboard di halaman login.

## Tahap 2 - Dynamic Import App Vue

Masalah:

- `resources/js/app.js` meng-import semua app Vue secara static.
- Halaman tertentu memuat kode yang tidak dipakai.

Target:

- Setiap app Vue hanya di-load jika mount node-nya ada.

Langkah implementasi:

- Ubah import static menjadi dynamic import.
- Contoh target perilaku:
  - Jika ada `#admin-dashboard-app`, baru import `./admin/AdminDashboardApp.vue`.
  - Jika ada `#booking-app`, baru import `./booking/BookingApp.vue`.
  - Jika ada `#booking-payment-app`, baru import `./booking/PaymentApp.vue`.
  - Jika ada `#booking-success-app`, baru import `./booking/BookingSuccessApp.vue`.

Checklist:

- Setiap halaman hanya memuat chunk yang relevan.
- Booking flow tetap berjalan.
- Admin dashboard tetap mount normal.
- Build Vite berhasil.

## Tahap 3 - Lazy Load Admin Pages

Masalah:

- Banyak page admin masih di-import static di `AdminDashboardApp.vue`.
- Page yang belum dibuka user tetap ikut masuk bundle awal.

Target:

- Page admin berat dimuat sebagai chunk terpisah.

Langkah implementasi:

- Gunakan `defineAsyncComponent` untuk page besar:
  - `BookingsPage`
  - `PackagesPage`
  - `AddOnsPage`
  - `StockPage`
  - `DesignsPage`
  - `UsersPage`
  - `QueuePage`
  - `PaymentsPage`
  - `TimeSlotsPage`
  - `PrinterSettingsPage`
  - `AppSettingsPage`
- Pertahankan komponen layout utama seperti sidebar/topbar sebagai static import jika ukurannya kecil.

Checklist:

- Sidebar navigation tetap responsif.
- Saat module dibuka pertama kali, chunk page terkait dimuat.
- Tidak ada blank screen saat async component loading.
- Tidak ada error hydration/mount.

## Tahap 4 - Kurangi Bootstrap Dashboard

Masalah:

- `AdminDashboardDataService::snapshot()` mengumpulkan terlalu banyak data di request pertama `/admin`.
- Setelah login, user harus menunggu banyak query dan payload besar sebelum dashboard tampil.

Target:

- Request awal dashboard hanya mengambil data yang benar-benar dibutuhkan untuk halaman dashboard utama.
- Data module sidebar dimuat saat user membuka module tersebut.

Data yang layak tetap dimuat awal:

- Summary dashboard utama.
- Queue live ringkas.
- Recent transactions terbatas.
- Recent activities terbatas.
- Current user.
- Navigation/config ringan.

Data yang sebaiknya lazy-load:

- `initialPackages`
- `initialAddOns`
- `initialDesigns`
- `initialUsers`
- `initialBranches`
- `initialTimeSlots`
- `initialBlackoutDates`
- `initialPrinterSettings`
- `initialPayments`
- `initialPaymentTransactionOptions`
- `initialReferralPayload`
- `initialInventoryItems`
- `initialInventoryMovements`

Checklist:

- `/admin` lebih cepat tampil setelah login.
- Module packages tetap load saat sidebar packages dibuka.
- Module payments tetap load saat sidebar payments dibuka.
- Module time slots tetap load saat sidebar time-slots dibuka.
- Tidak ada UI yang bergantung pada initial data yang sudah dipindahkan tanpa fallback.

## Tahap 5 - Lazy Load Data Per Module

Masalah:

- Beberapa module sudah punya endpoint data sendiri, tetapi sebagian initial data masih dibawa dari bootstrap.

Target:

- Setiap sidebar module memiliki fetch data sendiri.
- Data tidak dipanggil ulang jika sudah ada dan belum perlu refresh.

Langkah implementasi:

- Gunakan `moduleRegistry` sebagai titik masuk fetch per module.
- Pastikan setiap module punya loading state dan error state.
- Tambahkan tombol refresh manual untuk module penting.
- Gunakan `silent` refresh hanya untuk data real-time seperti bookings/queue.

Checklist:

- Module tampil dengan loading state yang jelas.
- Error module ditampilkan ramah user.
- Auto-refresh tidak berjalan saat user berada di module lain.

## Tahap 6 - Pagination Dan Filter Data Besar

Masalah:

- Data besar seperti time slots, payments, inventory movements, referrals, dan activity logs berpotensi dikirim terlalu banyak.

Target:

- Semua data besar memakai pagination, limit, search, dan filter.

Prioritas endpoint:

- Time slots: filter `branch_id`, `slot_date`, `is_bookable`, pagination.
- Payments: filter `branch_id`, `method`, `paid_date`, `transaction_status`, pagination.
- Inventory movements: pagination dan filter inventory item/date.
- Referrals: pagination untuk redemption rows.
- Activity logs: pagination dan filter module/user/date.

Checklist:

- Payload endpoint lebih kecil.
- Tabel tetap bisa mencari dan filter data.
- Pagination UI jelas.
- Query tetap cepat saat data besar.

## Tahap 7 - Optimasi Query Database

Masalah:

- Dashboard dan module admin memakai banyak query agregasi, relasi, count, dan list.
- Jika data bertambah, query tanpa index akan makin lambat.

Target:

- Query umum memakai index yang sesuai.
- Hindari N+1 query.
- Query list memakai `select()` field yang diperlukan saja.

Index yang perlu dievaluasi:

| Tabel | Kolom |
| --- | --- |
| `bookings` | `status`, `booking_date`, `branch_id`, `package_id`, `created_at` |
| `time_slots` | `branch_id`, `slot_date`, `start_time`, `is_bookable` |
| `transactions` | `branch_id`, `status`, `created_at` |
| `payments` | `transaction_id`, `paid_at`, `method` |
| `queue_tickets` | `branch_id`, `queue_date`, `status` |
| `inventory_movements` | `inventory_item_id`, `created_at` |
| `referral_redemptions` | `referral_code_id`, `redeemed_at`, `booking_id`, `transaction_id` |

Checklist:

- Jalankan explain/query profiler pada query lambat.
- Tambahkan migration index seperlunya.
- Pastikan index tidak berlebihan.
- Test endpoint dashboard dan list admin setelah index.

## Tahap 8 - Cache Data Jarang Berubah

Masalah:

- Settings, branch options, payment methods, dan beberapa config jarang berubah tetapi bisa dipanggil berkali-kali.

Target:

- Data jarang berubah di-cache dan di-invalidate saat update.

Kandidat cache:

- App settings public/admin.
- Branch aktif untuk dropdown.
- Payment methods enum.
- UI config.
- Summary ringan yang tidak harus real-time.

Checklist:

- Cache key jelas.
- Cache invalidated setelah admin update setting/branch.
- Tidak ada stale data yang berbahaya untuk booking/payment.

## Tahap 9 - Optimasi Gambar Dan Upload Media

Kondisi saat ini:

- Package sample photo maksimal 12 foto per package.
- Tiap file maksimal 5 MB.
- Format yang diterima: `jpg`, `jpeg`, `png`, `webp`.

Target:

- Gambar listing tidak menggunakan file original besar.
- Upload dikompresi/resize untuk web.

Langkah implementasi:

- Resize gambar upload ke ukuran maksimum yang masuk akal untuk web.
- Buat thumbnail untuk grid/listing.
- Gunakan `loading="lazy"` pada image di frontend.
- Pertimbangkan konversi WebP.
- Bersihkan file lama saat foto dihapus atau package dihapus.

Checklist:

- Upload tetap menerima format yang dibutuhkan.
- Gambar tampil cepat di halaman public dan admin.
- File lama tidak menumpuk di storage.

## Tahap 10 - Error Handling Production

Masalah:

- Error seperti `Failed to fetch` terlalu teknikal untuk user.
- HTTP error belum selalu diklasifikasikan.

Target:

- User mendapat pesan yang jelas dan actionable.
- Developer tetap mendapat detail teknikal di log.

Mapping pesan user:

| Kondisi | Pesan User |
| --- | --- |
| Network error / `Failed to fetch` | Tidak dapat terhubung ke server. Periksa koneksi internet, lalu coba lagi. |
| `419` | Sesi keamanan berakhir. Refresh halaman lalu coba lagi. |
| `401` | Sesi login berakhir. Silakan login ulang. |
| `403` | Anda tidak memiliki izin untuk menjalankan aksi ini. |
| `404` | Data atau halaman yang diminta tidak ditemukan. |
| `422` | Data yang diisi belum valid. Periksa kembali form. |
| `429` | Terlalu banyak request. Tunggu sebentar lalu coba lagi. |
| `500+` | Server sedang mengalami kendala. Silakan coba beberapa saat lagi atau hubungi admin. |

Langkah implementasi:

- Buat helper fetch terpusat di frontend.
- Normalisasi error response JSON dari Laravel.
- Tambahkan exception JSON handling di `bootstrap/app.php` untuk AJAX/API.
- Log detail error teknikal di server.

Checklist:

- User tidak lagi melihat `Failed to fetch` mentah.
- Error validasi tetap menampilkan field message.
- Session expired memberi instruksi refresh/login ulang.
- Error server tidak membocorkan stack trace saat `APP_DEBUG=false`.

## Tahap 11 - Production Build Dan Cache

Target:

- Production berjalan dengan asset build, config cache, route cache, view cache, dan debug off.

Checklist deployment:

- Set `.env`: `APP_ENV=production`.
- Set `.env`: `APP_DEBUG=false`.
- Set `.env`: `APP_URL` sesuai domain HTTPS production.
- Jalankan `composer install --no-dev --optimize-autoloader`.
- Jalankan `npm ci` jika dependency lock tersedia, atau `npm install` sesuai workflow project.
- Jalankan `npm run build`.
- Jalankan `php artisan config:cache`.
- Jalankan `php artisan route:cache`.
- Jalankan `php artisan view:cache`.
- Jalankan migration dengan `php artisan migrate --force`.
- Aktifkan PHP OPcache.
- Pastikan storage link aktif dengan `php artisan storage:link` jika dibutuhkan.

Checklist server:

- HTTPS aktif.
- `APP_URL` memakai `https://`.
- Jika menggunakan reverse proxy, trusted proxy harus benar.
- Static asset dari `/build` bisa di-cache browser/CDN.
- Upload storage public bisa diakses.

## Tahap 12 - Monitoring Dan Pengukuran

Target:

- Optimasi berbasis data, bukan asumsi.

Metrik yang perlu dicatat:

| Area | Target Awal |
| --- | --- |
| Login page load | < 1 detik setelah cache browser |
| `POST /admin/login` | < 500 ms pada koneksi normal |
| `GET /admin` first load | < 2-3 detik |
| Sidebar module fetch | < 1 detik untuk data normal |
| API error rate | Mendekati 0 untuk request valid |
| JavaScript initial chunk | Semakin kecil, idealnya hanya chunk halaman terkait |

Tools yang bisa digunakan:

- Browser DevTools Network.
- Laravel log.
- Slow query log database.
- Laravel Telescope hanya untuk staging/local, bukan production publik.
- Server metrics: CPU, RAM, disk IO.
- Web server access log untuk endpoint lambat.

## Urutan Eksekusi Rekomendasi

1. Pisahkan login layout agar tidak memuat bundle dashboard.
2. Ubah `resources/js/app.js` menjadi dynamic import per mount node.
3. Ubah page admin besar menjadi async component.
4. Kurangi isi `AdminDashboardDataService::snapshot()`.
5. Pastikan setiap module sidebar fetch data sendiri saat dibuka.
6. Tambahkan pagination untuk endpoint data besar.
7. Tambahkan index database berdasarkan query aktual.
8. Tambahkan helper fetch dan error message production.
9. Optimasi gambar upload dan lazy-load image.
10. Aktifkan production cache dan monitoring.

## Risiko Dan Catatan

- Mengurangi bootstrap data harus hati-hati karena beberapa UI mungkin masih membaca `initial...` props.
- Lazy-load data perlu loading state yang jelas agar user tidak melihat tabel kosong tanpa konteks.
- Cache settings harus di-invalidate saat admin mengubah setting.
- Index database perlu disesuaikan dengan query aktual agar tidak menambah beban write secara berlebihan.
- Error handling production tidak boleh membocorkan stack trace atau detail credential.

## Definition Of Done

- Login page tidak memuat kode dashboard.
- Dashboard admin tampil lebih cepat setelah login.
- Module sidebar memuat data saat dibuka.
- Endpoint besar memakai pagination/filter.
- Query lambat sudah diidentifikasi dan diberi index seperlunya.
- User mendapat pesan error yang jelas, bukan pesan teknikal mentah.
- `npm run build` berhasil.
- Test Laravel relevan berhasil.
- Production config dan cache sudah aktif.
