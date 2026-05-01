// booking.dart
class Booking {
  final int? recordId;
  final String id;
  final String customerName;
  final String phone;
  final String time;
  final String status;
  final String paymentType;
  final String paymentStatus;
  final int queueNumber;
  final double totalAmount;
  final double depositAmount;
  final bool canConfirmPayment;
  final bool canConfirmBooking;
  final bool canDeclineBooking;
  final String? email;
  final String? note;

  const Booking({
    this.recordId,
    required this.id,
    required this.customerName,
    required this.phone,
    required this.time,
    required this.status,
    this.paymentType = 'full',
    this.paymentStatus = 'unpaid',
    required this.queueNumber,
    this.totalAmount = 0,
    this.depositAmount = 0,
    this.canConfirmPayment = false,
    this.canConfirmBooking = false,
    this.canDeclineBooking = false,
    this.email,
    this.note,
  });
}

// customer.dart
class Customer {
  final String name;
  final String whatsapp;
  final int jumlahOrang;
  final String? email;
  final String? note;

  const Customer({
    required this.name,
    required this.whatsapp,
    required this.jumlahOrang,
    this.email,
    this.note,
  });
}

// package.dart
class Package {
  final String id;
  final String name;
  final String duration;
  final String prints;
  final double price;
  final String? iconPath;

  const Package({
    required this.id,
    required this.name,
    required this.duration,
    required this.prints,
    required this.price,
    this.iconPath,
  });
}

// addon.dart
class Addon {
  final String id;
  final String name;
  final String subtitle;
  final double price;
  final int? stock;
  final int maxQty;
  int quantity;

  Addon({
    required this.id,
    required this.name,
    required this.subtitle,
    required this.price,
    this.stock,
    this.maxQty = 99,
    this.quantity = 0,
  });
}

// order.dart
class OrderItem {
  final String name;
  final String subtitle;
  final double price;

  const OrderItem({
    required this.name,
    required this.subtitle,
    required this.price,
  });
}
