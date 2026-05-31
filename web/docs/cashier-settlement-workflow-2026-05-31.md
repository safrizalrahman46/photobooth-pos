# Cashier Settlement Workflow

## Tujuan

Fitur Setoran Kasir dipakai untuk mencocokkan uang cash yang diserahkan kasir ke owner. Laporan ini berbasis uang masuk per sesi kasir, bukan transaksi berstatus lunas.

## Konsep Utama

- Login hanya identitas akun.
- Sesi kasir adalah periode kerja dari buka sesi sampai tutup sesi.
- Payment wajib terhubung ke sesi aktif melalui `payments.cashier_session_id`.
- Setelah tutup sesi, sistem membuat snapshot di `cashier_settlements.snapshot`.
- Reprint selalu memakai snapshot, bukan hitung ulang dari transaksi live.
- Perubahan setelah tutup sesi harus masuk `cashier_settlement_corrections`.

## Rumus

```text
Total Penjualan   = Cash + QRIS + Transfer + Card
Non Cash          = QRIS + Transfer + Card
JML DISETOR CASH  = Cash Diterima - Pengeluaran Cash
Uang Laci         = ditampilkan sebagai disisakan, tidak ikut setor
```

## Payment Stage

```text
full        Pembayaran penuh pertama
dp          Pembayaran pertama yang belum melunasi total
pelunasan   Pembayaran berikutnya setelah sudah ada DP
extra_print Pembayaran tambah cetak
correction  Penyesuaian/koreksi manual
```

`INFO DP` di struk hanya breakdown. Nilainya sudah termasuk di `Total Penjualan`, sehingga tidak boleh dijumlah ulang.

## Proteksi Bug

- Payment tanpa sesi aktif ditolak.
- Sesi business date lama memblok pembayaran baru sampai sesi lama ditutup.
- Payment settlement memakai `net_amount` untuk mencegah overpayment/kembalian memperbesar setoran.
- Pengeluaran cash dikunci setelah tutup sesi.
- Snapshot menjaga struk kasir dan website owner tetap sama.
- Nomor setoran unik dengan format `SETOR-YYYYMMDD-0001`.

## Flow Operasional

```text
Kasir login
Kasir buka Sesi Kasir dan input Uang Laci
Kasir menerima pembayaran dan input pengeluaran cash bila ada
Kasir preview Tutup Sesi
Sistem membuat snapshot dan No Setoran
Kasir print struk
Owner buka menu Setoran Kasir di website dan cocokkan JML DISETOR CASH
Jika ada selisih/perubahan, owner membuat Koreksi Setoran
```

## Catatan QRIS

QRIS masih manual karena tidak ada payment gateway. QRIS masuk `Non Cash` dan tidak memengaruhi `JML DISETOR CASH`.

## Legacy Data

Transaksi lama yang belum punya `payments.cashier_session_id` tidak otomatis masuk setoran baru. Jika perlu, lakukan backfill terpisah dengan penanda legacy agar tidak mencampur data lama dan setoran operasional baru.
