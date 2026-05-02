# Time Slot Stage 1 Redesign - 2026-05-02

## Tujuan

- Menyamakan logic backend antara preview availability slot dan final save booking.
- Mengurangi kebingungan admin pada page `Time Slots` dengan istilah dan layout yang lebih jelas.

## Perubahan Backend

### Konsistensi kapasitas slot

- `app/Services/SlotService.php` diperluas agar menjadi owner logic untuk:
  - timezone branch slot
  - durasi slot
  - lookup slot yang benar-benar memuat sesi booking
  - hitung overlap booking aktif
  - hitung sisa kapasitas booking paralel
  - cek apakah package muat di slot

- `app/Services/BookingService.php`
  - final save booking tidak lagi sekadar menolak semua overlap.
  - sekarang booking baru masih boleh masuk jika `time_slots.capacity` belum penuh.

- `app/Services/AdminBookingManagementService.php`
  - booking admin create/update sekarang memakai konsep yang sama.
  - pesan error sekarang lebih spesifik saat kapasitas booking paralel sudah penuh.

### Guard admin time slot

- `app/Services/AdminTimeSlotService.php`
  - admin tidak bisa menurunkan kapasitas slot di bawah jumlah booking aktif yang sudah overlap.
  - admin tidak bisa menghapus slot yang masih dipakai booking aktif.

## Enrichment Data Admin Time Slots

`AdminTimeSlotService::rows()` sekarang mengirim insight tambahan per slot:

- `slot_duration_minutes`
- `active_bookings_count`
- `remaining_parallel_capacity`
- `is_full`
- `compatible_packages_count`
- `compatible_package_names`
- `longest_supported_duration_minutes`

Tujuannya agar admin langsung tahu:

- slot ini panjangnya berapa menit
- saat ini sudah dipakai berapa booking aktif
- masih sisa berapa booking paralel
- package mana yang muat pada slot ini

## Redesign UI Page Time Slots

File utama:

- `resources/js/admin/pages/TimeSlotsPage.vue`

Perubahan UX:

- Menambahkan blok penjelasan cara kerja slot di bagian atas.
- Mengubah label `Capacity` menjadi `Maks. Booking Paralel`.
- Menjelaskan bahwa kapasitas slot bukan jumlah orang, melainkan jumlah booking yang boleh overlap.
- Memisahkan mental model page menjadi:
  - penjelasan cara kerja
  - buat slot manual
  - generate slot otomatis
  - daftar slot
- Daftar slot sekarang menampilkan insight operasional per row:
  - branch dan tanggal
  - rentang jam
  - durasi slot
  - booking paralel max
  - booking aktif dan sisa kapasitas
  - package yang muat
- Bulk action wording dirapikan menjadi:
  - pilih semua hasil filter
  - bersihkan pilihan
  - buka booking slot terpilih
  - tutup booking slot terpilih

## File Yang Diubah

- `app/Services/SlotService.php`
- `app/Services/BookingService.php`
- `app/Services/AdminBookingManagementService.php`
- `app/Services/AdminTimeSlotService.php`
- `resources/js/admin/composables/useTimeSlotsModule.js`
- `resources/js/admin/pages/TimeSlotsPage.vue`
- `tests/Feature/AdminVueModulesTest.php`

## Test Coverage

Ditambahkan / diubah test untuk memastikan:

- availability menghormati booking paralel (`capacity`)
- `BookingService` mengizinkan booking overlap sampai kapasitas penuh
- booking berikutnya ditolak ketika kapasitas slot sudah habis

## Catatan PostgreSQL

- Database sebelumnya masih memiliki constraint exclusion `bookings_no_overlap` pada tabel `bookings`.
- Constraint ini memaksa semua booking aktif di branch yang sama tidak boleh overlap sama sekali.
- Karena Stage 1 memperkenalkan konsep `time_slots.capacity` sebagai batas booking paralel, constraint lama tersebut bertentangan dengan logic baru.
- Migration baru ditambahkan untuk melepas constraint legacy itu:
  - `database/migrations/2026_05_02_130000_drop_legacy_bookings_no_overlap_constraint.php`
- Sesudah migration dijalankan, sumber kebenaran overlap booking paralel berpindah ke:
  - `SlotService`
  - `BookingService`
  - `AdminBookingManagementService`

## Catatan Produk

Tahap 1 ini belum menambahkan konsep `base_people` / `max_people` di package.

Artinya, sesudah redesign ini:

- `Time Slot` mengatur rentang jam dan booking paralel
- `Package` masih mengatur durasi
- jumlah orang booking masih mengikuti model lama yang berbasis default + add-on extra person

Jika dibutuhkan, Tahap 2 berikutnya adalah menambahkan batas orang langsung di package.
