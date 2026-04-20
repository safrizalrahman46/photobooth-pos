class PaymentRecord {
  const PaymentRecord({
    required this.id,
    required this.paymentCode,
    required this.method,
    required this.amount,
    required this.referenceNo,
    required this.paidAt,
  });

  final int id;
  final String paymentCode;
  final String method;
  final double amount;
  final String? referenceNo;
  final String? paidAt;

  factory PaymentRecord.fromJson(Map<String, dynamic> json) {
    return PaymentRecord(
      id: (json['id'] as num?)?.toInt() ?? 0,
      paymentCode: json['payment_code']?.toString() ?? '-',
      method: json['method']?.toString() ?? 'cash',
      amount: (json['amount'] as num?)?.toDouble() ?? 0,
      referenceNo: json['reference_no']?.toString(),
      paidAt: json['paid_at']?.toString(),
    );
  }
}
