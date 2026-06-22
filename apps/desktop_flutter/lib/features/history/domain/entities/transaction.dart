import 'package:desktop_flutter/shared/models/transaction_item_line.dart';
import 'package:desktop_flutter/shared/models/transaction_record.dart';

enum TransactionStatus { lunas, pending, dp, batal }

extension TransactionStatusX on TransactionStatus {
  String get label {
    return switch (this) {
      TransactionStatus.lunas => 'Lunas',
      TransactionStatus.pending => 'Belum Dibayar',
      TransactionStatus.dp => 'DP / Belum Lunas',
      TransactionStatus.batal => 'Batal',
    };
  }
}

class Transaction {
  final int backendId;
  final String id;
  final DateTime waktu;
  final String namaPelanggan;
  final String branchName;
  final int? packageId;
  final String paket;
  final String? addOns;
  final List<TransactionItemLine> items;
  final double totalAmount;
  final double paidAmount;
  final TransactionStatus status;
  final TransactionRecord? rawRecord;

  double get remainingAmount => totalAmount - paidAmount;

  const Transaction({
    this.backendId = 0,
    required this.id,
    required this.waktu,
    required this.namaPelanggan,
    this.branchName = '',
    this.packageId,
    required this.paket,
    this.addOns,
    this.items = const [],
    required this.totalAmount,
    this.paidAmount = 0,
    required this.status,
    this.rawRecord,
  });

  String get paketDanAddOns {
    if (addOns != null && addOns!.isNotEmpty) {
      return '$paket + $addOns';
    }
    return paket;
  }
}
