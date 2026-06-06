// features/history/domain/entities/transaction.dart

import 'package:desktop_flutter/shared/models/transaction_item_line.dart';

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
  final int backendId;
  final String id; // e.g. "TRX-9402"
  final DateTime waktu;
  final String namaPelanggan;
  final String branchName;
  final int? packageId;
  final String paket;
  final String? addOns; // nullable, bisa tidak ada add-on
  final List<TransactionItemLine> items;
  final int totalBayar; // dalam rupiah, tanpa desimal
  final TransactionStatus status;

  const Transaction({
    this.backendId = 0,
    required this.id,
    required this.waktu,
    required this.namaPelanggan,
    this.branchName = '',
    this.packageId,
    required this.paket,
    this.addOns,
    this.items = const <TransactionItemLine>[],
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
