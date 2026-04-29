// booking.dart
class Booking {
  final String id;
  final String customerName;
  final String phone;
  final String time;
  final String status;
  final int queueNumber;
  final String? email;
  final String? note;

  const Booking({
    required this.id,
    required this.customerName,
    required this.phone,
    required this.time,
    required this.status,
    required this.queueNumber,
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
  int quantity;

  Addon({
    required this.id,
    required this.name,
    required this.subtitle,
    required this.price,
    this.stock,
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
