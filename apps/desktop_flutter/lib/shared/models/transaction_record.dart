import 'package:desktop_flutter/shared/models/payment_record.dart';
import 'package:desktop_flutter/shared/models/transaction_item_line.dart';

class TransactionRecord {
  const TransactionRecord({
    required this.id,
    required this.transactionCode,
    required this.branchId,
    required this.status,
    required this.totalAmount,
    required this.paidAmount,
    required this.changeAmount,
    required this.createdAt,
    required this.items,
    required this.payments,
  });

  final int id;
  final String transactionCode;
  final int branchId;
  final String status;
  final double totalAmount;
  final double paidAmount;
  final double changeAmount;
  final String? createdAt;
  final List<TransactionItemLine> items;
  final List<PaymentRecord> payments;

  factory TransactionRecord.fromJson(Map<String, dynamic> json) {
    final rawItems = json['items'];
    final rawPayments = json['payments'];

    return TransactionRecord(
      id: (json['id'] as num?)?.toInt() ?? 0,
      transactionCode: json['transaction_code']?.toString() ?? '-',
      branchId: (json['branch_id'] as num?)?.toInt() ?? 0,
      status: json['status']?.toString() ?? 'unpaid',
      totalAmount: (json['total_amount'] as num?)?.toDouble() ?? 0,
      paidAmount: (json['paid_amount'] as num?)?.toDouble() ?? 0,
      changeAmount: (json['change_amount'] as num?)?.toDouble() ?? 0,
      createdAt: json['created_at']?.toString(),
      items: rawItems is List
          ? rawItems
                .whereType<Map<String, dynamic>>()
                .map(TransactionItemLine.fromJson)
                .toList()
          : <TransactionItemLine>[],
      payments: rawPayments is List
          ? rawPayments
                .whereType<Map<String, dynamic>>()
                .map(PaymentRecord.fromJson)
                .toList()
          : <PaymentRecord>[],
    );
  }
}
