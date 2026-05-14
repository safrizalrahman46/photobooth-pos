class ReferralPreview {
  const ReferralPreview({
    required this.referralCodeId,
    required this.referralCode,
    required this.sourceName,
    required this.sourceType,
    required this.discountType,
    required this.discountValue,
    required this.discountAmount,
    required this.subtotalAmount,
    required this.finalAmount,
  });

  final int referralCodeId;
  final String referralCode;
  final String sourceName;
  final String sourceType;
  final String discountType;
  final double discountValue;
  final double discountAmount;
  final double subtotalAmount;
  final double finalAmount;

  factory ReferralPreview.fromJson(Map<String, dynamic> json) {
    return ReferralPreview(
      referralCodeId: (json['referral_code_id'] as num?)?.toInt() ?? 0,
      referralCode: json['referral_code']?.toString() ?? '',
      sourceName: json['source_name']?.toString() ?? '',
      sourceType: json['source_type']?.toString() ?? '',
      discountType: json['discount_type']?.toString() ?? 'fixed',
      discountValue: (json['discount_value'] as num?)?.toDouble() ?? 0,
      discountAmount: (json['discount_amount'] as num?)?.toDouble() ?? 0,
      subtotalAmount: (json['subtotal_amount'] as num?)?.toDouble() ?? 0,
      finalAmount: (json['final_amount'] as num?)?.toDouble() ?? 0,
    );
  }
}
