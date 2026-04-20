class BookingItem {
  const BookingItem({
    required this.id,
    required this.bookingCode,
    required this.customerName,
    required this.customerPhone,
    required this.bookingDate,
    required this.status,
    required this.startAt,
  });

  final int id;
  final String bookingCode;
  final String customerName;
  final String customerPhone;
  final String bookingDate;
  final String status;
  final String? startAt;

  factory BookingItem.fromJson(Map<String, dynamic> json) {
    return BookingItem(
      id: (json['id'] as num?)?.toInt() ?? 0,
      bookingCode: json['booking_code']?.toString() ?? '-',
      customerName: json['customer_name']?.toString() ?? '-',
      customerPhone: json['customer_phone']?.toString() ?? '-',
      bookingDate: json['booking_date']?.toString() ?? '-',
      status: json['status']?.toString() ?? 'pending',
      startAt: json['start_at']?.toString(),
    );
  }
}
