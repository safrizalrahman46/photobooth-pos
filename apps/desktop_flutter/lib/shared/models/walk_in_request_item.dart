import 'package:desktop_flutter/shared/models/queue_ticket_item.dart';
import 'package:desktop_flutter/shared/models/transaction_record.dart';

class WalkInRequestItem {
  const WalkInRequestItem({
    required this.id,
    required this.requestCode,
    required this.branchName,
    required this.packageName,
    required this.customerName,
    required this.customerPhone,
    required this.totalAmount,
    required this.status,
    required this.expiresAt,
    required this.transactionId,
    required this.queueTicketId,
  });

  final int id;
  final String requestCode;
  final String branchName;
  final String packageName;
  final String customerName;
  final String customerPhone;
  final double totalAmount;
  final String status;
  final String? expiresAt;
  final int? transactionId;
  final int? queueTicketId;

  bool get isPendingPayment => status == 'pending_payment';

  factory WalkInRequestItem.fromJson(Map<String, dynamic> json) {
    return WalkInRequestItem(
      id: (json['id'] as num?)?.toInt() ?? 0,
      requestCode: json['request_code']?.toString() ?? '-',
      branchName: json['branch_name']?.toString() ?? '-',
      packageName: json['package_name']?.toString() ?? '-',
      customerName: json['customer_name']?.toString() ?? '-',
      customerPhone: json['customer_phone']?.toString() ?? '-',
      totalAmount: (json['total_amount'] as num?)?.toDouble() ?? 0,
      status: json['status']?.toString() ?? 'pending_payment',
      expiresAt: json['expires_at']?.toString(),
      transactionId: (json['transaction_id'] as num?)?.toInt(),
      queueTicketId: (json['queue_ticket_id'] as num?)?.toInt(),
    );
  }
}

class WalkInConfirmResult {
  const WalkInConfirmResult({
    required this.walkInRequest,
    required this.transaction,
    required this.queueTicket,
  });

  final WalkInRequestItem walkInRequest;
  final TransactionRecord transaction;
  final QueueTicketItem queueTicket;

  factory WalkInConfirmResult.fromJson(Map<String, dynamic> json) {
    final request = json['walk_in_request'];
    final transaction = json['transaction'];
    final queueTicket = json['queue_ticket'];

    if (request is! Map<String, dynamic> ||
        transaction is! Map<String, dynamic> ||
        queueTicket is! Map<String, dynamic>) {
      throw const FormatException('Respons konfirmasi QR walk-in tidak valid.');
    }

    return WalkInConfirmResult(
      walkInRequest: WalkInRequestItem.fromJson(request),
      transaction: TransactionRecord.fromJson(transaction),
      queueTicket: QueueTicketItem.fromJson(queueTicket),
    );
  }
}
