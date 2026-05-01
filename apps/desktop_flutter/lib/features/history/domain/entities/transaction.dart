// features/history/domain/entities/transaction.dart

/// Enum untuk status transaksi
enum TransactionStatus { lunas, pending, batal }

/// Extension untuk label & warna status
extension TransactionStatusX on TransactionStatus {
  String get label {
    switch (this) {
      case TransactionStatus.lunas:
        return 'Lunas';
      case TransactionStatus.pending:
        return 'Pending';
      case TransactionStatus.batal:
        return 'Batal';
    }
  }
}

/// Entity utama transaksi
class Transaction {
  final String id; // e.g. "TRX-9402"
  final DateTime waktu;
  final String namaPelanggan;
  final String paket;
  final String? addOns; // nullable, bisa tidak ada add-on
  final int totalBayar; // dalam rupiah, tanpa desimal
  final TransactionStatus status;

  const Transaction({
    required this.id,
    required this.waktu,
    required this.namaPelanggan,
    required this.paket,
    this.addOns,
    required this.totalBayar,
    required this.status,
  });

  /// Gabungan paket & add-ons untuk ditampilkan di tabel
  String get paketDanAddOns {
    if (addOns != null && addOns!.isNotEmpty) {
      return '$paket + $addOns';
    }
    return paket;
  }
}
