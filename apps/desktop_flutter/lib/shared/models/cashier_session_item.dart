class CashierSessionItem {
  const CashierSessionItem({
    required this.id,
    required this.userId,
    required this.branchId,
    required this.businessDate,
    required this.status,
    required this.openedAt,
    required this.closedAt,
    required this.openingCash,
    required this.closingCash,
    required this.isLateClose,
    required this.notes,
    required this.branchName,
    required this.settlementId,
  });

  final int id;
  final int userId;
  final int branchId;
  final String? businessDate;
  final String status;
  final String? openedAt;
  final String? closedAt;
  final double openingCash;
  final double? closingCash;
  final bool isLateClose;
  final String? notes;
  final String branchName;
  final int? settlementId;

  bool get isOpen => status == 'open';

  factory CashierSessionItem.fromJson(Map<String, dynamic> json) {
    final branch = json['branch'];

    return CashierSessionItem(
      id: (json['id'] as num?)?.toInt() ?? 0,
      userId: (json['user_id'] as num?)?.toInt() ?? 0,
      branchId: (json['branch_id'] as num?)?.toInt() ?? 0,
      businessDate: json['business_date']?.toString(),
      status: json['status']?.toString() ?? 'closed',
      openedAt: json['opened_at']?.toString(),
      closedAt: json['closed_at']?.toString(),
      openingCash: (json['opening_cash'] as num?)?.toDouble() ?? 0,
      closingCash: (json['closing_cash'] as num?)?.toDouble(),
      isLateClose: json['is_late_close'] == true,
      notes: json['notes']?.toString(),
      branchName: branch is Map<String, dynamic>
          ? branch['name']?.toString() ?? '-'
          : '-',
      settlementId: (json['settlement_id'] as num?)?.toInt(),
    );
  }
}
