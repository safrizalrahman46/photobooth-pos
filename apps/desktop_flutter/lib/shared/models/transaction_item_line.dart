class TransactionItemLine {
  const TransactionItemLine({
    required this.id,
    required this.itemType,
    required this.itemName,
    required this.qty,
    required this.unitPrice,
    required this.lineTotal,
  });

  final int id;
  final String itemType;
  final String itemName;
  final double qty;
  final double unitPrice;
  final double lineTotal;

  factory TransactionItemLine.fromJson(Map<String, dynamic> json) {
    return TransactionItemLine(
      id: (json['id'] as num?)?.toInt() ?? 0,
      itemType: json['item_type']?.toString() ?? 'manual',
      itemName: json['item_name']?.toString() ?? '-',
      qty: (json['qty'] as num?)?.toDouble() ?? 0,
      unitPrice: (json['unit_price'] as num?)?.toDouble() ?? 0,
      lineTotal: (json['line_total'] as num?)?.toDouble() ?? 0,
    );
  }
}
