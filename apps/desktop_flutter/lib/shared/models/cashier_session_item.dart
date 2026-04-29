class CashierSessionItem {
  const CashierSessionItem({
    required this.id,
    required this.userId,
    required this.branchId,
    required this.status,
    required this.openedAt,
    required this.closedAt,
    required this.openingCash,
    required this.closingCash,
    required this.notes,
    required this.branchName,
  });

  final int id;
  final int userId;
  final int branchId;
  final String status;
  final String? openedAt;
  final String? closedAt;
  final double openingCash;
  final double? closingCash;
  final String? notes;
  final String branchName;

  bool get isOpen => status == 'open';

  factory CashierSessionItem.fromJson(Map<String, dynamic> json) {
    final branch = json['branch'];

    return CashierSessionItem(
      id: (json['id'] as num?)?.toInt() ?? 0,
      userId: (json['user_id'] as num?)?.toInt() ?? 0,
      branchId: (json['branch_id'] as num?)?.toInt() ?? 0,
      status: json['status']?.toString() ?? 'closed',
      openedAt: json['opened_at']?.toString(),
      closedAt: json['closed_at']?.toString(),
      openingCash: (json['opening_cash'] as num?)?.toDouble() ?? 0,
      closingCash: (json['closing_cash'] as num?)?.toDouble(),
      notes: json['notes']?.toString(),
      branchName: branch is Map<String, dynamic>
          ? branch['name']?.toString() ?? '-'
          : '-',
    );
  }
}
