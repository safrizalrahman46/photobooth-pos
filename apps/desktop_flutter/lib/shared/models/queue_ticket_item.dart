class QueueTicketItem {
  const QueueTicketItem({
    required this.id,
    required this.queueCode,
    required this.queueNumber,
    required this.customerName,
    required this.status,
    required this.sourceType,
    required this.queueDate,
    required this.branchId,
    required this.branchName,
  });

  final int id;
  final String queueCode;
  final int queueNumber;
  final String customerName;
  final String status;
  final String sourceType;
  final String queueDate;
  final int branchId;
  final String branchName;

  factory QueueTicketItem.fromJson(Map<String, dynamic> json) {
    final branch = json['branch'];

    return QueueTicketItem(
      id: (json['id'] as num?)?.toInt() ?? 0,
      queueCode: json['queue_code']?.toString() ?? '-',
      queueNumber: (json['queue_number'] as num?)?.toInt() ?? 0,
      customerName: json['customer_name']?.toString() ?? '-',
      status: json['status']?.toString() ?? 'waiting',
      sourceType: json['source_type']?.toString() ?? 'booking',
      queueDate: json['queue_date']?.toString() ?? '-',
      branchId: (json['branch_id'] as num?)?.toInt() ?? 0,
      branchName: branch is Map<String, dynamic>
          ? branch['name']?.toString() ?? '-'
          : '-',
    );
  }
}
