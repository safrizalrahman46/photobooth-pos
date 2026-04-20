class ReportSummary {
  const ReportSummary({
    required this.grossSales,
    required this.paidSales,
    required this.onlineBookingSales,
    required this.combinedPaidSales,
    required this.transactionCount,
    required this.totalBookings,
    required this.doneBookings,
    required this.cancelledBookings,
    required this.totalQueue,
    required this.finishedQueue,
    required this.cancelledQueue,
  });

  final double grossSales;
  final double paidSales;
  final double onlineBookingSales;
  final double combinedPaidSales;
  final int transactionCount;
  final int totalBookings;
  final int doneBookings;
  final int cancelledBookings;
  final int totalQueue;
  final int finishedQueue;
  final int cancelledQueue;

  factory ReportSummary.fromJson(Map<String, dynamic> json) {
    final sales = json['sales'] as Map<String, dynamic>? ?? {};
    final bookings = json['bookings'] as Map<String, dynamic>? ?? {};
    final queue = json['queue'] as Map<String, dynamic>? ?? {};

    return ReportSummary(
      grossSales: (sales['gross_sales'] as num?)?.toDouble() ?? 0,
      paidSales: (sales['paid_sales'] as num?)?.toDouble() ?? 0,
      onlineBookingSales:
          (sales['online_booking_sales'] as num?)?.toDouble() ?? 0,
      combinedPaidSales:
          (sales['combined_paid_sales'] as num?)?.toDouble() ?? 0,
      transactionCount: (sales['transaction_count'] as num?)?.toInt() ?? 0,
      totalBookings: (bookings['total'] as num?)?.toInt() ?? 0,
      doneBookings: (bookings['done'] as num?)?.toInt() ?? 0,
      cancelledBookings: (bookings['cancelled'] as num?)?.toInt() ?? 0,
      totalQueue: (queue['total'] as num?)?.toInt() ?? 0,
      finishedQueue: (queue['finished'] as num?)?.toInt() ?? 0,
      cancelledQueue: (queue['cancelled'] as num?)?.toInt() ?? 0,
    );
  }
}
