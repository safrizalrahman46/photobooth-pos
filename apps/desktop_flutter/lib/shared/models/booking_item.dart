class BookingItem {
  const BookingItem({
    required this.id,
    required this.bookingCode,
    required this.branchId,
    required this.branchName,
    required this.packageId,
    required this.packageName,
    required this.customerName,
    required this.customerPhone,
    required this.customerEmail,
    required this.bookingDate,
    required this.status,
    required this.paymentType,
    required this.paymentStatus,
    required this.startAt,
    required this.subtotalAmount,
    required this.discountAmount,
    required this.referralCode,
    required this.referralDiscountAmount,
    required this.totalAmount,
    required this.depositAmount,
    required this.paidAmount,
    required this.remainingAmount,
    required this.transferProofUrl,
    required this.canConfirmBooking,
    required this.canConfirmPayment,
    required this.canDeclineBooking,
    required this.approvedAt,
    required this.addOns,
  });

  final int id;
  final String bookingCode;
  final int branchId;
  final String branchName;
  final int packageId;
  final String packageName;
  final String customerName;
  final String customerPhone;
  final String customerEmail;
  final String bookingDate;
  final String status;
  final String paymentType;
  final String paymentStatus;
  final String? startAt;
  final double subtotalAmount;
  final double discountAmount;
  final String referralCode;
  final double referralDiscountAmount;
  final double totalAmount;
  final double depositAmount;
  final double paidAmount;
  final double remainingAmount;
  final String transferProofUrl;
  final bool canConfirmBooking;
  final bool canConfirmPayment;
  final bool canDeclineBooking;
  final String approvedAt;
  final List<BookingAddOnLine> addOns;

  factory BookingItem.fromJson(Map<String, dynamic> json) {
    final rawAddOns = json['add_ons'] ?? json['addons'];

    return BookingItem(
      id: (json['id'] as num?)?.toInt() ?? 0,
      bookingCode: json['booking_code']?.toString() ?? '-',
      branchId: (json['branch_id'] as num?)?.toInt() ?? 0,
      branchName: json['branch_name']?.toString() ?? '-',
      packageId: (json['package_id'] as num?)?.toInt() ?? 0,
      packageName: json['package_name']?.toString() ?? '-',
      customerName: json['customer_name']?.toString() ?? '-',
      customerPhone: json['customer_phone']?.toString() ?? '-',
      customerEmail: json['customer_email']?.toString() ?? '',
      bookingDate: json['booking_date']?.toString() ?? '-',
      status: json['status']?.toString() ?? 'pending',
      paymentType: json['payment_type']?.toString() ?? 'full',
      paymentStatus: json['payment_status']?.toString() ?? 'unpaid',
      startAt: json['start_at']?.toString(),
      subtotalAmount: (json['subtotal_amount'] as num?)?.toDouble() ?? 0,
      discountAmount: (json['discount_amount'] as num?)?.toDouble() ?? 0,
      referralCode: json['referral_code']?.toString() ?? '',
      referralDiscountAmount:
          (json['referral_discount_amount'] as num?)?.toDouble() ?? 0,
      totalAmount: (json['total_amount'] as num?)?.toDouble() ?? 0,
      depositAmount: (json['deposit_amount'] as num?)?.toDouble() ?? 0,
      paidAmount: (json['paid_amount'] as num?)?.toDouble() ?? 0,
      remainingAmount: (json['remaining_amount'] as num?)?.toDouble() ?? 0,
      transferProofUrl: json['transfer_proof_url']?.toString() ?? '',
      canConfirmBooking: json['can_confirm_booking'] == true,
      canConfirmPayment: json['can_confirm_payment'] == true,
      canDeclineBooking: json['can_decline_booking'] == true,
      approvedAt: json['approved_at']?.toString() ?? '',
      addOns: rawAddOns is List
          ? rawAddOns
              .whereType<Map<String, dynamic>>()
              .map(BookingAddOnLine.fromJson)
              .toList()
          : <BookingAddOnLine>[],
    );
  }
}

class BookingAddOnLine {
  const BookingAddOnLine({
    required this.addOnId,
    required this.label,
    required this.qty,
    required this.unitPrice,
    required this.lineTotal,
  });

  final int? addOnId;
  final String label;
  final int qty;
  final double unitPrice;
  final double lineTotal;

  factory BookingAddOnLine.fromJson(Map<String, dynamic> json) {
    return BookingAddOnLine(
      addOnId: (json['add_on_id'] as num?)?.toInt(),
      label: json['label']?.toString() ?? json['name']?.toString() ?? '-',
      qty: (json['qty'] as num?)?.toInt() ?? 0,
      unitPrice: (json['unit_price'] as num?)?.toDouble() ?? 0,
      lineTotal: (json['line_total'] as num?)?.toDouble() ?? 0,
    );
  }
}
