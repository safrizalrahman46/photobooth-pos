import 'package:desktop_flutter/shared/models/queue_ticket_item.dart';
import 'package:desktop_flutter/shared/models/transaction_record.dart';

class WalkInRequestAddOn {
  final int addOnId;
  final String name;
  final int qty;
  final double unitPrice;
  final double lineTotal;
  final bool isPackage;

  const WalkInRequestAddOn({
    required this.addOnId,
    required this.name,
    required this.qty,
    required this.unitPrice,
    required this.lineTotal,
    this.isPackage = false,
  });

  factory WalkInRequestAddOn.fromJson(Map<String, dynamic> json) {
    return WalkInRequestAddOn(
      addOnId: (json['add_on_id'] as num?)?.toInt() ?? (json['id'] as num?)?.toInt() ?? 0,
      name: (json['name'] ?? json['label'] ?? '').toString(),
      qty: (json['qty'] as num?)?.toInt() ?? 0,
      unitPrice: (json['unit_price'] ?? json['price'] as num?)?.toDouble() ?? 0,
      lineTotal: (json['line_total'] as num?)?.toDouble() ?? 0,
      isPackage: json['is_package'] == true,
    );
  }
}

class WalkInRequestItem {
  const WalkInRequestItem({
    required this.id,
    required this.requestCode,
    required this.branchName,
    required this.packageId,
    required this.packageName,
    required this.packagePrice,
    required this.customerName,
    required this.customerPhone,
    this.customerEmail,
    required this.totalAmount,
    required this.subtotalAmount,
    required this.addOns,
    required this.status,
    this.paymentMethod,
    required this.expiresAt,
    required this.transactionId,
    required this.queueTicketId,
    this.socialMediaConsent = false,
  });

  final int id;
  final String requestCode;
  final String branchName;
  final int packageId;
  final String packageName;
  final double packagePrice;
  final String customerName;
  final String customerPhone;
  final String? customerEmail;
  final double totalAmount;
  final double subtotalAmount;
  final List<WalkInRequestAddOn> addOns;
  final String status;
  final String? paymentMethod;
  final String? expiresAt;
  final int? transactionId;
  final int? queueTicketId;
  final bool socialMediaConsent;

  bool get isPendingPayment => status == 'pending_payment';

  double get addOnsTotal => addOns.fold(0, (sum, a) => sum + a.lineTotal);

  factory WalkInRequestItem.fromJson(Map<String, dynamic> json) {
    return WalkInRequestItem(
      id: (json['id'] as num?)?.toInt() ?? 0,
      requestCode: json['request_code']?.toString() ?? '-',
      branchName: json['branch_name']?.toString() ?? '-',
      packageId: (json['package_id'] as num?)?.toInt() ?? 0,
      packageName: json['package_name']?.toString() ?? '-',
      packagePrice: (json['package_price'] as num?)?.toDouble() ?? 0,
      customerName: json['customer_name']?.toString() ?? '-',
      customerPhone: json['customer_phone']?.toString() ?? '-',
      totalAmount: (json['total_amount'] as num?)?.toDouble() ?? 0,
      subtotalAmount: (json['subtotal_amount'] as num?)?.toDouble() ?? 0,
      customerEmail: json['customer_email']?.toString(),
      addOns: (json['add_ons'] as List?)
          ?.map((e) => WalkInRequestAddOn.fromJson(e as Map<String, dynamic>))
          .toList() ?? [],
      status: json['status']?.toString() ?? 'pending_payment',
      paymentMethod: json['payment_method']?.toString(),
      expiresAt: json['expires_at']?.toString(),
      transactionId: (json['transaction_id'] as num?)?.toInt(),
      queueTicketId: (json['queue_ticket_id'] as num?)?.toInt(),
      socialMediaConsent: json['social_media_consent'] == true,
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
