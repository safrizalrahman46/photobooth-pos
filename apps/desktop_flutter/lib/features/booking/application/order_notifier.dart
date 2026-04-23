// application/order_notifier.dart

import 'package:flutter/foundation.dart';
import '../domain/order_state.dart';

class OrderNotifier extends ChangeNotifier {
  OrderState _state = OrderState(
    customerName: 'Budi Santoso',
    whatsapp: '081199881234',
    jumlahOrang: 4,
    selectedPackageId: 'mandi_bola',
    addOns: [
      AddOn(
        id: 'cetak_4r',
        name: 'Cetak Foto 4R',
        subtitle: 'Sisa Stok: 45',
        price: 15000,
        iconType: 'print',
        quantity: 2,
      ),
      AddOn(
        id: 'frame_custom',
        name: 'Frame Custom',
        subtitle: 'Varian Exclusive Wood',
        price: 25000,
        iconType: 'frame',
        quantity: 0,
      ),
      AddOn(
        id: 'extra_person',
        name: 'Extra Person',
        subtitle: 'Max 2 person additional',
        price: 10000,
        iconType: 'person',
        quantity: 0,
      ),
    ],
    isPaid: true,
    paymentMethod: 'TUNAI',
  );

  OrderState get state => _state;

  static final List<Package> packages = [
    Package(
      id: 'basic',
      name: 'Basic',
      duration: '15 Menit',
      printInfo: '2 Print',
      price: 50000,
      iconType: 'camera',
    ),
    Package(
      id: 'mandi_bola',
      name: 'MANDI BOLA',
      duration: '20 Menit',
      printInfo: '4 Print',
      price: 85000,
      iconType: 'swim',
    ),
    Package(
      id: 'minimarket',
      name: 'Minimarket',
      duration: '25 Menit',
      printInfo: '6 Print',
      price: 120000,
      iconType: 'cart',
    ),
  ];

  static final List<BookingQueue> queues = [
    BookingQueue(
      code: 'TB-9821',
      customerName: 'Sarah Johnson',
      phone: '+62 812-3344-xxxx',
      time: '10:30 AM',
      status: 'PILIH PAKET',
    ),
    BookingQueue(
      code: 'TB-9822',
      customerName: 'Budi Santoso',
      phone: '+62 811-9988-xxxx',
      time: 'NOW',
      status: 'SEDANG SESI',
      isActive: true,
    ),
    BookingQueue(
      code: 'TB-9823',
      customerName: 'Amanda Clarissa',
      phone: '+62 819-2211-xxxx',
      time: '11:15 AM',
      status: 'ANTREAN 2',
    ),
    BookingQueue(
      code: 'TB-9824',
      customerName: 'Dimas Pratama',
      phone: '+62 857-4455-xxxx',
      time: '11:45 AM',
      status: 'ANTREAN 3',
    ),
    BookingQueue(
      code: 'TB-9825',
      customerName: 'Citra Lestari',
      phone: '+62 822-6677-xxxx',
      time: '12:15 PM',
      status: 'ANTREAN 4',
    ),
  ];

  void selectPackage(String packageId) {
    _state = _state.copyWith(selectedPackageId: packageId);
    notifyListeners();
  }

  void incrementAddOn(String addOnId) {
    final updatedAddOns = _state.addOns.map((a) {
      if (a.id == addOnId) {
        return AddOn(
          id: a.id,
          name: a.name,
          subtitle: a.subtitle,
          price: a.price,
          iconType: a.iconType,
          quantity: a.quantity + 1,
        );
      }
      return a;
    }).toList();
    _state = _state.copyWith(addOns: updatedAddOns);
    notifyListeners();
  }

  void decrementAddOn(String addOnId) {
    final updatedAddOns = _state.addOns.map((a) {
      if (a.id == addOnId && a.quantity > 0) {
        return AddOn(
          id: a.id,
          name: a.name,
          subtitle: a.subtitle,
          price: a.price,
          iconType: a.iconType,
          quantity: a.quantity - 1,
        );
      }
      return a;
    }).toList();
    _state = _state.copyWith(addOns: updatedAddOns);
    notifyListeners();
  }

  void setPaymentMethod(String method) {
    _state = _state.copyWith(paymentMethod: method);
    notifyListeners();
  }

  double get grandTotal {
    final selectedPkg = packages.firstWhere(
      (p) => p.id == _state.selectedPackageId,
      orElse: () => packages[0],
    );
    double total = selectedPkg.price;
    for (final addon in _state.addOns) {
      total += addon.price * addon.quantity;
    }
    return total;
  }

  List<OrderSummaryItem> get summaryItems {
    final List<OrderSummaryItem> items = [];
    if (_state.selectedPackageId != null) {
      final pkg = packages.firstWhere(
        (p) => p.id == _state.selectedPackageId,
        orElse: () => packages[0],
      );
      items.add(
        OrderSummaryItem(
          name: '${pkg.name} Package',
          subtitle: '${pkg.duration} Session',
          price: pkg.price,
        ),
      );
    }
    for (final addon in _state.addOns) {
      if (addon.quantity > 0) {
        items.add(
          OrderSummaryItem(
            name: '${addon.name} (x${addon.quantity})',
            subtitle: addon.subtitle,
            price: addon.price * addon.quantity,
          ),
        );
      }
    }
    return items;
  }
}
