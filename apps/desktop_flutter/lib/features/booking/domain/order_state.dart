// domain/order_state.dart

class Package {
  final String id;
  final String name;
  final String duration;
  final String printInfo;
  final double price;
  final String iconType; // 'camera', 'swim', 'cart'

  const Package({
    required this.id,
    required this.name,
    required this.duration,
    required this.printInfo,
    required this.price,
    required this.iconType,
  });
}

class AddOn {
  final String id;
  final String name;
  final String subtitle;
  final double price;
  final String iconType; // 'print', 'frame', 'person'
  int quantity;

  AddOn({
    required this.id,
    required this.name,
    required this.subtitle,
    required this.price,
    required this.iconType,
    this.quantity = 0,
  });
}

class BookingQueue {
  final String code;
  final String customerName;
  final String phone;
  final String time;
  final String status; // 'PILIH PAKET', 'SEDANG SESI', 'ANTREAN 2', etc.
  final bool isActive;

  const BookingQueue({
    required this.code,
    required this.customerName,
    required this.phone,
    required this.time,
    required this.status,
    this.isActive = false,
  });
}

class OrderSummaryItem {
  final String name;
  final String subtitle;
  final double price;

  const OrderSummaryItem({
    required this.name,
    required this.subtitle,
    required this.price,
  });
}

class OrderState {
  final String customerName;
  final String whatsapp;
  final int jumlahOrang;
  final String? selectedPackageId;
  final List<AddOn> addOns;
  final String voucherCode;
  final bool isPaid;
  final String paymentMethod; // 'TUNAI' or 'QRIS'

  const OrderState({
    this.customerName = '',
    this.whatsapp = '',
    this.jumlahOrang = 0,
    this.selectedPackageId,
    this.addOns = const [],
    this.voucherCode = '',
    this.isPaid = false,
    this.paymentMethod = 'TUNAI',
  });

  OrderState copyWith({
    String? customerName,
    String? whatsapp,
    int? jumlahOrang,
    String? selectedPackageId,
    List<AddOn>? addOns,
    String? voucherCode,
    bool? isPaid,
    String? paymentMethod,
  }) {
    return OrderState(
      customerName: customerName ?? this.customerName,
      whatsapp: whatsapp ?? this.whatsapp,
      jumlahOrang: jumlahOrang ?? this.jumlahOrang,
      selectedPackageId: selectedPackageId ?? this.selectedPackageId,
      addOns: addOns ?? this.addOns,
      voucherCode: voucherCode ?? this.voucherCode,
      isPaid: isPaid ?? this.isPaid,
      paymentMethod: paymentMethod ?? this.paymentMethod,
    );
  }
}
