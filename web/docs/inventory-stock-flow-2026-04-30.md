# Inventory Stock Flow - 2026-04-30

## Ringkasan

Implementasi ini memisahkan `Add-on` sebagai produk/benefit yang dijual dari `Inventory Item` sebagai barang stok fisik. Stock tidak lagi ditrack berdasarkan nama add-on, tetapi berdasarkan master barang seperti `Kertas Foto 4R`, `Blank Gantungan Kunci Akrilik`, dan item fisik lainnya.

## Struktur Data Baru

- `inventory_items`: master barang stok fisik.
- `package_inventory_items`: resep konsumsi barang untuk setiap package per booking.
- `add_on_inventory_items`: resep konsumsi barang untuk setiap add-on per qty add-on.
- `inventory_movements`: ledger stok untuk manual movement dan auto deduction booking.

Kolom legacy inventory di `add_ons` tetap dipertahankan untuk transisi aman, tetapi alur stock baru menggunakan `inventory_items`. Field `add_ons.available_stock` dan `add_ons.low_stock_threshold` tidak lagi menjadi sumber stok, tidak ditampilkan di UI Add-ons, dan tidak diterima oleh request create/update Add-ons.

## Effective Availability Add-on

- Add-on tidak menyimpan stok sendiri.
- Availability add-on dihitung dari mapping `add_on_inventory_items` dan stok `inventory_items`.
- Jika add-on memakai satu barang, availability = `floor(inventory_items.available_stock / qty_per_unit)`.
- Jika add-on memakai beberapa barang, availability = nilai terkecil dari semua barang yang dibutuhkan.
- Jika tidak ada mapping barang, status ditampilkan sebagai `Not mapped`.
- Status `Ready`, `Low`, dan `Out` mengikuti kondisi barang stok yang menjadi sumber mapping.

## Alur Verifikasi Booking

- Customer booking dan upload bukti transfer manual QR.
- Admin/owner mengecek bukti pembayaran di detail booking.
- Saat booking diverifikasi, termasuk pembayaran DP, sistem otomatis menghitung kebutuhan stok dari package dan add-on.
- Sistem melakukan row lock pada barang stok, mengecek kecukupan stok, mengurangi stok, lalu mencatat `inventory_movements`.
- Jika stok tidak cukup, verifikasi booking ditolak dengan pesan barang yang kurang.
- Deduction idempotent: booking yang sama tidak akan mengurangi stok dua kali.

## Contoh Mapping

- Package apa pun: `Kertas Foto 4R` sebanyak 1 lembar per booking.
- Add-on `+1 cetak 4R`: `Kertas Foto 4R` sebanyak 1 lembar per qty.
- Add-on `+1 orang (include cetak 1 lembar 4R)`: `Kertas Foto 4R` sebanyak 1 lembar per qty.
- Add-on `Gantungan kunci akrilik`: `Blank Gantungan Kunci Akrilik` sebanyak 1 pcs per qty.

## UI Admin

- `Add-ons`: tetap mengelola katalog add-on, harga, max qty, status, dan mapping konsumsi barang.
- Kolom `Effective Availability` di Add-ons bersifat read-only dan dihitung dari mapping barang.
- `Packages`: menambah mapping konsumsi barang per package.
- `Stock`: mengelola master barang, stok, threshold, manual stock in/out, dan riwayat movement.

Manual stock in/out hanya dilakukan dari halaman `Stock` melalui endpoint inventory item. Endpoint legacy add-on stock movement sudah tidak dipakai.

Style UI tetap mengikuti desain dashboard yang sudah ada; perubahan berfokus pada struktur dan alur kerja.

## Integritas Harga Add-on

Booking publik tidak lagi mempercayai harga add-on dari frontend. Frontend hanya mengirim `add_on_id` dan `qty`; backend mengambil nama, harga, scope package, dan max qty dari database.

## Endpoint Baru

- `GET /admin/stock-data`
- `POST /admin/inventory-items`
- `PUT /admin/inventory-items/{inventoryItem}`
- `DELETE /admin/inventory-items/{inventoryItem}`
- `POST /admin/inventory-items/{inventoryItem}/movement`

Endpoint legacy `POST /admin/add-ons/{addOn}/stock-movement` dihapus dari route admin agar tidak ada dua sumber mutasi stok.

## Verifikasi

- Migration sudah dijalankan lokal.
- Frontend build berhasil menggunakan `npm.cmd run build`.
- Test suite Laravel belum bisa berjalan penuh karena environment PHP tidak memiliki driver `pdo_sqlite` untuk database test `:memory:`.
