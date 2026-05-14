import 'package:desktop_flutter/core/session/api_session.dart';
import 'package:desktop_flutter/shared/models/pos_walk_in_checkout_result.dart';
import 'package:desktop_flutter/shared/models/referral_preview.dart';
import 'package:flutter/material.dart';
import '../domain/entities/booking.dart';

class BookingController extends ChangeNotifier {
  BookingController() {
    loadInitialData();
  }

  bool isLoading = false;
  bool isSubmitting = false;
  String? errorMessage;
  int? selectedBranchId;
  String selectedBranchName = '-';
  PosWalkInCheckoutResult? lastCheckoutResult;

  // Customer
  String customerName = '';
  String whatsapp = '';
  String email = '';
  String note = '';
  int jumlahOrang = 1;

  // Queue list
  List<Booking> queues = [];

  int selectedQueueIndex = 0;

  // Packages
  List<Package> packages = [];

  int selectedPackageIndex = 0;

  // Addons
  List<Addon> addons = [];

  // Referral
  String referralCode = '';
  ReferralPreview? referralPreview;
  bool isApplyingReferral = false;
  String? referralMessage;
  String? referralError;

  // Payment
  String selectedPayment = 'TUNAI'; // 'TUNAI' or 'QRIS'

  // Computed
  Package get selectedPackage => packages.isEmpty
      ? const Package(id: '0', name: '-', duration: '-', prints: '-', price: 0)
      : packages[selectedPackageIndex.clamp(0, packages.length - 1).toInt()];

  List<Addon> get selectedAddons =>
      addons.where((a) => a.quantity > 0).toList();

  double get packagePrice => selectedPackage.price;

  double get addonsTotal =>
      addons.fold(0, (sum, a) => sum + (a.price * a.quantity));

  double get subtotalTotal => packagePrice + addonsTotal;

  double get referralDiscount => referralPreview?.discountAmount ?? 0;

  double get grandTotal {
    final total = subtotalTotal - referralDiscount;
    return total < 0 ? 0 : total;
  }

  Future<void> loadInitialData() async {
    final client = ApiSession.client;

    if (client == null) {
      return;
    }

    isLoading = true;
    errorMessage = null;
    notifyListeners();

    try {
      final branches = await client.fetchBranches();

      if (branches.isNotEmpty) {
        final branch = branches.first;
        selectedBranchId = branch.id;
        selectedBranchName = branch.name;
      }

      final packageRows = await client.fetchPackages(branchId: selectedBranchId);

      packages = packageRows.map((row) {
        return Package(
          id: row.id.toString(),
          name: row.name,
          duration: '${row.durationMinutes} Menit',
          prints: 'Package',
          price: row.basePrice,
        );
      }).toList();

      selectedPackageIndex = packages.isEmpty
          ? 0
          : selectedPackageIndex.clamp(0, packages.length - 1).toInt();

      await _loadAddOnsForSelectedPackage();

      final bookingRows = await client.fetchBookings(
        branchId: selectedBranchId,
        perPage: 100,
      );

      queues = bookingRows.asMap().entries.map((entry) {
        final booking = entry.value;
        return Booking(
          recordId: booking.id,
          id: booking.bookingCode,
          customerName: booking.customerName,
          phone: booking.customerPhone,
          time: _formatBookingTime(booking.startAt),
          status: booking.status,
          paymentType: booking.paymentType,
          paymentStatus: booking.paymentStatus,
          queueNumber: entry.key + 1,
          totalAmount: booking.totalAmount,
          depositAmount: booking.depositAmount,
          canConfirmPayment: booking.canConfirmPayment,
          canConfirmBooking: booking.canConfirmBooking,
          canDeclineBooking: booking.canDeclineBooking,
          email: booking.customerEmail,
          note: booking.packageName,
        );
      }).toList();
      selectedQueueIndex = queues.isEmpty
          ? 0
          : selectedQueueIndex.clamp(0, queues.length - 1).toInt();
    } catch (error) {
      errorMessage = error.toString();
    } finally {
      isLoading = false;
      notifyListeners();
    }
  }

  // Methods
  void selectQueue(int index) {
    selectedQueueIndex = index;
    notifyListeners();
  }

  void selectPackage(int index) {
    selectedPackageIndex = index;
    for (final addon in addons) {
      addon.quantity = 0;
    }
    _clearReferralPreview();
    notifyListeners();
    _loadAddOnsForSelectedPackage();
  }

  void incrementAddon(int index) {
    final stock = addons[index].stock;

    if (stock != null && addons[index].quantity >= stock) {
      return;
    }

    addons[index].quantity++;
    _clearReferralPreview();
    notifyListeners();
  }

  void decrementAddon(int index) {
    if (addons[index].quantity > 0) {
      addons[index].quantity--;
      _clearReferralPreview();
      notifyListeners();
    }
  }

  void setPayment(String method) {
    selectedPayment = method;
    notifyListeners();
  }

  void updateCustomerName(String val) {
    customerName = val;
    notifyListeners();
  }

  void updateWhatsapp(String val) {
    whatsapp = val;
    notifyListeners();
  }

  void updateEmail(String val) {
    email = val;
    notifyListeners();
  }

  void updateJumlahOrang(String val) {
    final parsed = int.tryParse(val);

    if (parsed == null || parsed < 1) {
      return;
    }

    jumlahOrang = parsed;
    notifyListeners();
  }

  void updateNote(String val) {
    note = val;
    notifyListeners();
  }

  void updateReferralCode(String val) {
    referralCode = val.toUpperCase();
    _clearReferralPreview(keepMessage: false);
    notifyListeners();
  }

  Future<void> applyReferral() async {
    final client = ApiSession.client;
    final branchId = selectedBranchId;
    final packageId = int.tryParse(selectedPackage.id);
    final code = referralCode.trim();

    referralMessage = null;
    referralError = null;

    if (code.isEmpty) {
      referralPreview = null;
      notifyListeners();
      return;
    }

    if (client == null || branchId == null || packageId == null || packageId <= 0) {
      referralError = 'Data cabang/paket belum siap.';
      notifyListeners();
      return;
    }

    isApplyingReferral = true;
    notifyListeners();

    try {
      final preview = await client.validateReferralCode(
        referralCode: code,
        branchId: branchId,
        packageId: packageId,
        subtotalAmount: subtotalTotal,
      );

      referralPreview = preview;
      referralCode = preview.referralCode;
      referralMessage = 'Diskon referal diterapkan.';
    } catch (error) {
      referralPreview = null;
      referralError = error.toString();
    } finally {
      isApplyingReferral = false;
      notifyListeners();
    }
  }

  Future<void> accBooking() async {
    final client = ApiSession.client;

    if (client == null ||
        selectedQueueIndex < 0 ||
        selectedQueueIndex >= queues.length) {
      return;
    }

    final booking = queues[selectedQueueIndex];

    if (booking.recordId == null) {
      return;
    }

    try {
      if (booking.canConfirmPayment) {
        final paymentAmount = booking.paymentType == 'dp50'
            ? booking.depositAmount > 0
                ? booking.depositAmount
                : booking.totalAmount * 0.5
            : booking.totalAmount;

        await client.confirmBookingPayment(
          bookingId: booking.recordId!,
          method: 'transfer',
          amount: paymentAmount,
          notes: 'Verified from desktop app.',
        );
      } else if (booking.canConfirmBooking) {
        await client.confirmBooking(
          bookingId: booking.recordId!,
          reason: 'Verified from desktop app.',
        );
      }

      await loadInitialData();
    } catch (error) {
      errorMessage = error.toString();
      notifyListeners();
    }

    if (selectedQueueIndex >= 0 && selectedQueueIndex < queues.length) {
      notifyListeners();
    }
  }

  Future<void> cancelBooking() async {
    final client = ApiSession.client;

    if (client == null ||
        selectedQueueIndex < 0 ||
        selectedQueueIndex >= queues.length) {
      return;
    }

    final booking = queues[selectedQueueIndex];

    if (booking.recordId == null || !booking.canDeclineBooking) {
      return;
    }

    try {
      await client.declineBooking(
        bookingId: booking.recordId!,
        reason: 'Declined from desktop app.',
      );
      await loadInitialData();
    } catch (error) {
      errorMessage = error.toString();
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

  Future<PosWalkInCheckoutResult?> checkoutWalkIn() async {
    final client = ApiSession.client;
    final branchId = selectedBranchId;

    if (client == null || branchId == null || packages.isEmpty) {
      errorMessage = 'Data cabang/paket belum siap.';
      notifyListeners();
      return null;
    }

    if (customerName.trim().isEmpty || whatsapp.trim().isEmpty) {
      errorMessage = 'Nama pelanggan dan WhatsApp wajib diisi.';
      notifyListeners();
      return null;
    }

    isSubmitting = true;
    errorMessage = null;
    notifyListeners();

    try {
      final result = await client.checkoutWalkIn(
        branchId: branchId,
        packageId: int.parse(selectedPackage.id),
        customerName: customerName.trim(),
        customerPhone: whatsapp.trim(),
        paymentMethod: selectedPayment == 'QRIS' ? 'qris' : 'cash',
        paidAmount: grandTotal,
        referralCode: referralPreview == null ? null : referralCode.trim(),
        notes: note.trim().isEmpty ? null : note.trim(),
        addons: selectedAddons
            .map((addon) => {
                  'add_on_id': int.parse(addon.id),
                  'qty': addon.quantity,
                })
            .toList(),
      );

      lastCheckoutResult = result;
      await loadInitialData();
      return result;
    } catch (error) {
      errorMessage = error.toString();
      notifyListeners();
      return null;
    } finally {
      isSubmitting = false;
      notifyListeners();
    }
  }

  Future<void> _loadAddOnsForSelectedPackage() async {
    final client = ApiSession.client;
    final packageId = int.tryParse(selectedPackage.id);

    if (client == null || packageId == null || packageId <= 0) {
      addons = [];
      notifyListeners();
      return;
    }

    try {
      final addOnRows = await client.fetchAddOns(packageId: packageId);

      addons = addOnRows.map((item) {
        final stock = item.effectiveAvailableStock;
        final maxQty = item.maxQty < 1 ? 1 : item.maxQty;
        final stockLimit = stock == null || stock > maxQty ? maxQty : stock;

        return Addon(
          id: item.id.toString(),
          name: item.name,
          subtitle: stock == null ? 'Maks $maxQty item' : 'Sisa stok: $stock',
          price: item.price,
          stock: stockLimit,
          maxQty: maxQty,
        );
      }).toList();
    } catch (error) {
      errorMessage = error.toString();
      addons = [];
    }

    notifyListeners();
  }

  void _clearReferralPreview({bool keepMessage = true}) {
    referralPreview = null;
    referralError = null;

    if (!keepMessage) {
      referralMessage = null;
    } else if (referralCode.trim().isNotEmpty) {
      referralMessage = 'Apply ulang kode referal setelah paket/add-on berubah.';
    }
  }

  String _formatBookingTime(String? value) {
    if (value == null || value.isEmpty) {
      return '-';
    }

    final parsed = DateTime.tryParse(value);

    if (parsed == null) {
      return value;
    }

    final local = parsed.toLocal();
    return '${local.hour.toString().padLeft(2, '0')}:${local.minute.toString().padLeft(2, '0')}';
  }
}
