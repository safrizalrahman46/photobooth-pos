# Desktop POS API Integration - 2026-05-01

## Ringkasan

- Flutter desktop POS sekarang memakai API Laravel untuk package, add-on, booking, antrean, transaksi, dan laporan dasar.
- POS walk-in checkout memakai endpoint tunggal agar transaksi, pembayaran, antrean, dan pemotongan stok terjadi dalam satu flow backend.
- Verifikasi booking customer dari website `/booking` bisa dilakukan dari desktop app melalui endpoint API yang reuse `AdminBookingManagementService`.
- Print struk tahap awal memakai PDF + Windows print dialog melalui package Flutter `printing`.

## Endpoint Baru/Diaktifkan

- `GET /api/v1/add-ons`: daftar add-on aktif untuk katalog/POS, termasuk effective stock dari mapping `inventory_items`.
- `GET /api/v1/manage/add-ons`: daftar add-on untuk user dengan akses katalog.
- `GET /api/v1/manage/inventory-items`: daftar barang fisik inventory untuk user dengan akses katalog.
- `POST /api/v1/pos/walk-in/checkout`: checkout walk-in POS.
- `POST /api/v1/bookings/{booking}/confirm-payment`: konfirmasi pembayaran booking manual transfer.
- `POST /api/v1/bookings/{booking}/confirm`: verifikasi booking yang sudah memiliki pembayaran.
- `POST /api/v1/bookings/{booking}/decline`: decline booking yang belum memiliki bukti pembayaran.
- `GET /api/v1/bookings/{booking}/transfer-proof`: stream bukti transfer booking.

## POS Walk-In Checkout

Endpoint `POST /api/v1/pos/walk-in/checkout` menerima `branch_id`, `package_id`, data customer, metode pembayaran, nominal bayar opsional, dan add-on yang dipilih.

Backend melakukan langkah berikut dalam transaction:

- Validasi package aktif dan sesuai branch.
- Resolve harga add-on dari database, bukan dari payload Flutter.
- Membuat `queue_ticket` dengan source `walk_in`.
- Membuat `transaction` beserta item package/add-on.
- Mencatat `payment` bila ada `paid_amount`.
- Memotong stok fisik melalui mapping package/add-on ke `inventory_items`.
- Mengembalikan `transaction` dan `queue_ticket` untuk ditampilkan/print di Flutter.

## Booking Verification Desktop

Desktop app membaca `GET /api/v1/bookings` untuk daftar booking customer dari website.

Flow tombol ACC:

- Jika `can_confirm_payment = true`, desktop memanggil `confirm-payment`.
- Untuk booking `payment_type = dp50`, nominal konfirmasi memakai `deposit_amount` bila ada, atau fallback 50% dari `total_amount`.
- Untuk booking full payment, nominal konfirmasi memakai `total_amount`.
- Setelah pembayaran terkonfirmasi, backend auto-verify booking jika sudah ada pembayaran dan akan memotong stok.
- Jika booking sudah punya pembayaran tetapi belum approved, desktop memanggil `confirm`.

Flow decline:

- Desktop memanggil `decline` hanya saat `can_decline_booking = true`.
- Backend tetap menolak decline untuk booking yang sudah approved, done/cancelled, atau memiliki transfer proof.

## Inventory Deduction

- Booking customer: stok dipotong ketika booking diverifikasi/approved melalui `InventoryService::deductForVerifiedBooking`.
- POS walk-in: stok dipotong ketika transaksi checkout dibuat melalui `InventoryService::deductForTransaction`.
- Deduction memakai source idempotent agar transaksi/booking yang sama tidak double deduct.
- Jika stok tidak cukup, backend mengembalikan error validasi dan checkout/verifikasi dibatalkan.

## Receipt Printing

- Flutter `ReceiptPrinter` membuat PDF struk ukuran thermal default 80mm.
- Struk berisi nomor transaksi, kode antrean, cabang, kasir, item, total, pembayaran, dan kembalian.
- Output memakai Windows print dialog via `Printing.layoutPdf`.
- Direct raw ESC/POS belum diaktifkan di tahap ini.

## Catatan Operasional

- Desktop app memakai `ApiSession` untuk menyimpan session aktif di runtime dan membuat `ApiClient` ber-token sama dengan login.
- `AppConfig.defaultApiBaseUrl` tetap mengarah ke `http://127.0.0.1:8000/api/v1`.
- Role cashier sudah memiliki permission `booking.manage`, `queue.manage`, dan `transaction.manage`, sehingga dapat checkout walk-in dan verifikasi booking.
