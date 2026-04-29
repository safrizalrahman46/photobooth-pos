import 'package:flutter/material.dart';
import '../domain/entities/booking.dart';

class BookingController extends ChangeNotifier {
  // Customer
  String customerName = 'Budi Santoso';
  String whatsapp = '081199881234';
  String email = 'budisantoso@gmail.com';
  String note = 'Acara Ulang Tahun';
  int jumlahOrang = 4;

  // Queue list
  List<Booking> queues = [
    Booking(
      id: 'TB-9821',
      customerName: 'Sarah Johnson',
      phone: '+62 812-3344-xxxx',
      time: '10:30 AM',
      status: 'PILIH PAKET',
      queueNumber: 1,
    ),
    Booking(
      id: 'TB-9822',
      customerName: 'Budi Santoso',
      phone: '+62 811-9988-xxxx',
      time: 'NOW',
      status: 'SEDANG SESI',
      queueNumber: 2,
    ),
    Booking(
      id: 'TB-9823',
      customerName: 'Amanda Clarissa',
      phone: '+62 819-2211-xxxx',
      time: '11:15 AM',
      status: 'ANTREAN 2',
      queueNumber: 3,
    ),
    Booking(
      id: 'TB-9824',
      customerName: 'Dimas Pratama',
      phone: '+62 857-4455-xxxx',
      time: '11:45 AM',
      status: 'ANTREAN 3',
      queueNumber: 4,
    ),
    Booking(
      id: 'TB-9825',
      customerName: 'Citra Lestari',
      phone: '+62 822-6677-xxxx',
      time: '12:15 PM',
      status: 'ANTREAN 4',
      queueNumber: 5,
    ),
  ];

  int selectedQueueIndex = 1;

  // Packages
  List<Package> packages = [
    Package(
      id: 'basic',
      name: 'Basic',
      duration: '15 Menit',
      prints: '2 Print',
      price: 50000,
    ),
    Package(
      id: 'mandi_bola',
      name: 'MANDI BOLA',
      duration: '20 Menit',
      prints: '4 Print',
      price: 85000,
    ),
    Package(
      id: 'minimarket',
      name: 'Minimarket',
      duration: '25 Menit',
      prints: '6 Print',
      price: 120000,
    ),
  ];

  int selectedPackageIndex = 1;

  // Addons
  List<Addon> addons = [
    Addon(
      id: 'cetak_4r',
      name: 'Cetak Foto 4R',
      subtitle: 'Sisa Stok: 45',
      price: 15000,
      stock: 45,
      quantity: 2,
    ),
    Addon(
      id: 'frame_custom',
      name: 'Frame Custom',
      subtitle: 'Varian Exclusive Wood',
      price: 25000,
      quantity: 0,
    ),
    Addon(
      id: 'extra_person',
      name: 'Extra Person',
      subtitle: 'Max 2 person additional',
      price: 10000,
      quantity: 0,
    ),
  ];

  // Voucher
  String voucherCode = 'KODEPROMO';
  bool voucherApplied = false;

  // Payment
  String selectedPayment = 'TUNAI'; // 'TUNAI' or 'QRIS'

  // Computed
  Package get selectedPackage => packages[selectedPackageIndex];

  List<Addon> get selectedAddons =>
      addons.where((a) => a.quantity > 0).toList();

  double get packagePrice => selectedPackage.price;

  double get addonsTotal =>
      addons.fold(0, (sum, a) => sum + (a.price * a.quantity));

  double get grandTotal => packagePrice + addonsTotal;

  // Methods
  void selectQueue(int index) {
    selectedQueueIndex = index;
    notifyListeners();
  }

  void selectPackage(int index) {
    selectedPackageIndex = index;
    notifyListeners();
  }

  void incrementAddon(int index) {
    addons[index].quantity++;
    notifyListeners();
  }

  void decrementAddon(int index) {
    if (addons[index].quantity > 0) {
      addons[index].quantity--;
      notifyListeners();
    }
  }

  void setPayment(String method) {
    selectedPayment = method;
    notifyListeners();
  }

  void updateEmail(String val) {
    email = val;
    notifyListeners();
  }

  void updateNote(String val) {
    note = val;
    notifyListeners();
  }

  void applyVoucher() {
    voucherApplied = !voucherApplied;
    notifyListeners();
  }

  void accBooking() {
    if (selectedQueueIndex >= 0 && selectedQueueIndex < queues.length) {
      // Mock: change status to 'TERKONFIRMASI' or similar
      // In real app, this might move it to the actual queue
      notifyListeners();
    }
  }

  void cancelBooking() {
    if (selectedQueueIndex >= 0 && selectedQueueIndex < queues.length) {
      queues.removeAt(selectedQueueIndex);
      if (selectedQueueIndex >= queues.length) {
        selectedQueueIndex = queues.length - 1;
      }
      notifyListeners();
    }
  }

  void deleteBooking() {
    if (selectedQueueIndex >= 0 && selectedQueueIndex < queues.length) {
      queues.removeAt(selectedQueueIndex);
      if (selectedQueueIndex >= queues.length) {
        selectedQueueIndex = queues.length - 1;
      }
      notifyListeners();
    }
  }
}
