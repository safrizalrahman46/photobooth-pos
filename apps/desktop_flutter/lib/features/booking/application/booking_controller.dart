import 'dart:typed_data';

import 'package:desktop_flutter/core/session/api_session.dart';
import 'package:desktop_flutter/core/network/request_error_message.dart';
import 'package:desktop_flutter/core/utils/date_util.dart';
import 'package:desktop_flutter/shared/models/booking_item.dart';
import 'package:desktop_flutter/shared/models/pos_walk_in_checkout_result.dart';
import 'package:desktop_flutter/shared/models/referral_preview.dart';
import 'package:flutter/material.dart';
import '../domain/entities/booking.dart';

class BookingController extends ChangeNotifier {
  bool _disposed = false;

  BookingController() {
    Future.microtask(() => loadInitialData());
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
  bool allowSharePhotos = true;

  // Queue list
  List<Booking> queues = [];

  int selectedQueueIndex = 0;

  Booking get selectedBooking {
    if (selectedQueueIndex < 0 || selectedQueueIndex >= queues.length) {
      return const Booking(
        id: '-',
        customerName: '-',
        phone: '-',
        time: '-',
        status: 'pending',
        queueNumber: 0,
      );
    }
    return queues[selectedQueueIndex];
  }

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
    safeNotify();

    try {
      final session = await client.fetchCurrentCashierSession();
      if (_disposed) return;

      final branches = await client.fetchBranches();
      if (_disposed) return;

      if (session != null && session.isOpen) {
        selectedBranchId = session.branchId;
        selectedBranchName = session.branchName;
      } else if (branches.isNotEmpty) {
        final branch = branches.first;
        selectedBranchId = branch.id;
        selectedBranchName = branch.name;
      }

      final packageRows = await client.fetchPackages(
        branchId: selectedBranchId,
      );
      if (_disposed) return;

      packages = packageRows.map((row) {
        return Package(
          id: row.id.toString(),
          name: row.name,
          duration: '${row.durationMinutes} Menit',
          prints: 'Paket',
          price: row.basePrice,
          samplePhotos: row.samplePhotos ?? [],
        );
      }).toList();

      selectedPackageIndex = packages.isEmpty
          ? 0
          : selectedPackageIndex.clamp(0, packages.length - 1).toInt();

      await _loadAddOnsForSelectedPackage();
      if (_disposed) return;

      final bookingRows = await client.fetchBookings(
        branchId: selectedBranchId,
        status: 'pending',
        perPage: 100,
      );
      if (_disposed) return;

      final actionableBookings = bookingRows
          .where(_isActionableBooking)
          .toList();

      queues = actionableBookings.asMap().entries.map((entry) {
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
      errorMessage = resolveRequestErrorMessage(
        error,
        fallback: 'Data booking belum dapat dimuat.',
      );
    } finally {
      isLoading = false;
      safeNotify();
    }
  }

  // Methods
  void selectQueue(int index) {
    selectedQueueIndex = index;
    allowSharePhotos = true;
    safeNotify();
  }

  void selectPackage(int index) {
    selectedPackageIndex = index;
    for (final addon in addons) {
      addon.quantity = 0;
    }
    _clearReferralPreview();
    safeNotify();
    _loadAddOnsForSelectedPackage();
  }

  bool _isActionableBooking(dynamic booking) {
    final status = booking.status.toString().toLowerCase();

    if (status != 'pending') {
      return false;
    }

    if (booking.approvedAt.toString().isNotEmpty) {
      return false;
    }

    return booking.canConfirmPayment ||
        booking.canConfirmBooking ||
        booking.canDeclineBooking;
  }

  void incrementAddon(int index) {
    final stock = addons[index].stock;

    if (stock != null && addons[index].quantity >= stock) {
      return;
    }

    addons[index].quantity++;
    _clearReferralPreview();
    safeNotify();
  }

  void decrementAddon(int index) {
    if (addons[index].quantity > 0) {
      addons[index].quantity--;
      _clearReferralPreview();
      safeNotify();
    }
  }

  void setPayment(String method) {
    selectedPayment = method;
    safeNotify();
  }

  void updateCustomerName(String val) {
    customerName = val;
    safeNotify();
  }

  void updateWhatsapp(String val) {
    whatsapp = val.replaceAll(RegExp(r'\D'), '');
    safeNotify();
  }

  void updateEmail(String val) {
    email = val;
    safeNotify();
  }

  void updateJumlahOrang(String val) {
    final parsed = int.tryParse(val);

    if (parsed == null || parsed < 1) {
      return;
    }

    jumlahOrang = parsed;
    safeNotify();
  }

  void updateNote(String val) {
    note = val;
    safeNotify();
  }

  void updateAllowSharePhotos(bool? val) {
    allowSharePhotos = val ?? false;
    safeNotify();
  }

  void toggleAllowSharePhotos(bool? val) {
    allowSharePhotos = val ?? false;
    safeNotify();
  }

  void updateReferralCode(String val) {
    referralCode = val.toUpperCase();
    _clearReferralPreview(keepMessage: false);
    safeNotify();
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
      safeNotify();
      return;
    }

    if (client == null ||
        branchId == null ||
        packageId == null ||
        packageId <= 0) {
      referralError = 'Data cabang/paket belum siap.';
      safeNotify();
      return;
    }

    isApplyingReferral = true;
    safeNotify();

    try {
      final preview = await client.validateReferralCode(
        referralCode: code,
        branchId: branchId,
        packageId: packageId,
        subtotalAmount: subtotalTotal,
      );
      if (_disposed) return;

      referralPreview = preview;
      referralCode = preview.referralCode;
      referralMessage = 'Diskon referal diterapkan.';
    } catch (error) {
      referralPreview = null;
      referralError = resolveRequestErrorMessage(
        error,
        fallback:
            'Kode referal tidak dapat digunakan. Periksa kembali kodenya.',
      );
    } finally {
      isApplyingReferral = false;
      safeNotify();
    }
  }

  Future<void> accBooking({
    String? paymentMethod,
    double? paymentAmount,
    String? referenceNo,
    String? notes,
  }) async {
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
        final amount = paymentAmount ?? (booking.paymentType == 'dp50'
            ? booking.depositAmount > 0
                  ? booking.depositAmount
                  : booking.totalAmount * 0.5
            : booking.totalAmount);

        final consentNote = allowSharePhotos
            ? '[Izin Share: YA]'
            : '[Izin Share: TIDAK]';

        final finalNotes = notes != null && notes.isNotEmpty
            ? '$notes $consentNote'
            : 'Diverifikasi dari aplikasi desktop. $consentNote';

        await client.confirmBookingPayment(
          bookingId: booking.recordId!,
          method: paymentMethod ?? 'transfer',
          amount: amount,
          referenceNo: referenceNo,
          notes: finalNotes,
        );
        if (_disposed) return;
      } else if (booking.canConfirmBooking) {
        final consentNote = allowSharePhotos
            ? '[Izin Share: YA]'
            : '[Izin Share: TIDAK]';

        await client.confirmBooking(
          bookingId: booking.recordId!,
          reason: 'Diverifikasi dari aplikasi desktop. $consentNote',
        );
      }

      await loadInitialData();
    } catch (error) {
      errorMessage = resolveRequestErrorMessage(
        error,
        fallback: 'Booking belum dapat diverifikasi. Coba lagi.',
      );
      safeNotify();
    }

    if (selectedQueueIndex >= 0 && selectedQueueIndex < queues.length) {
      safeNotify();
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
        reason: 'Ditolak dari aplikasi desktop.',
      );
      if (_disposed) return;
      await loadInitialData();
    } catch (error) {
      errorMessage = resolveRequestErrorMessage(
        error,
        fallback: 'Booking belum dapat ditolak. Coba lagi.',
      );
      safeNotify();
    }
  }

  Future<BookingItem?> fetchBookingDetail(int bookingId) async {
    final client = ApiSession.client;
    if (client == null) return null;

    try {
      return await client.fetchBookingDetail(bookingId: bookingId);
    } catch (_) {
      return null;
    }
  }

  Future<Uint8List?> downloadProofImage(int bookingId) async {
    final client = ApiSession.client;
    if (client == null) return null;

    return client.downloadRaw('/bookings/$bookingId/transfer-proof');
  }

  Future<PosWalkInCheckoutResult?> checkoutWalkIn(double paidAmount) async {
    final client = ApiSession.client;
    final branchId = selectedBranchId;

    if (client == null || branchId == null || packages.isEmpty) {
      errorMessage = 'Data cabang/paket belum siap.';
      safeNotify();
      return null;
    }

    if (customerName.trim().isEmpty || whatsapp.trim().isEmpty) {
      errorMessage = 'Nama pelanggan dan nomor telepon wajib diisi.';
      safeNotify();
      return null;
    }

    isSubmitting = true;
    errorMessage = null;
    safeNotify();

    try {
      final consentSuffix = allowSharePhotos ? '[Izin Share: YA]' : '[Izin Share: TIDAK]';
      final finalNote = note.trim().isEmpty ? consentSuffix : '${note.trim()} $consentSuffix';

      final result = await client.checkoutWalkIn(
        branchId: branchId,
        packageId: int.parse(selectedPackage.id),
        customerName: customerName.trim(),
        customerPhone: whatsapp.trim(),
        paymentMethod: selectedPayment == 'QRIS' ? 'qris' : 'cash',
        paidAmount: paidAmount,
        referralCode: referralPreview == null ? null : referralCode.trim(),
        notes: finalNote.trim().isEmpty ? null : finalNote.trim(),
        addons: selectedAddons
            .map(
              (addon) => {
                'add_on_id': int.parse(addon.id),
                'qty': addon.quantity,
              },
            )
            .toList(),
      );

      lastCheckoutResult = result;
      await loadInitialData();
      return result;
    } catch (error) {
      errorMessage = resolveRequestErrorMessage(
        error,
        fallback: 'Checkout walk-in belum berhasil. Coba lagi.',
      );
      safeNotify();
      return null;
    } finally {
      isSubmitting = false;
      safeNotify();
    }
  }

  Future<void> _loadAddOnsForSelectedPackage() async {
    final client = ApiSession.client;
    final packageId = int.tryParse(selectedPackage.id);

    if (client == null || packageId == null || packageId <= 0) {
      addons = [];
      safeNotify();
      return;
    }

    try {
      final addOnRows = await client.fetchAddOns(packageId: packageId);
      if (_disposed) return;

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
      errorMessage = resolveRequestErrorMessage(
        error,
        fallback: 'Add-on belum dapat dimuat.',
      );
      addons = [];
    }

    safeNotify();
  }

  void _clearReferralPreview({bool keepMessage = true}) {
    referralPreview = null;
    referralError = null;

    if (!keepMessage) {
      referralMessage = null;
    } else if (referralCode.trim().isNotEmpty) {
      referralMessage =
          'Terapkan ulang kode referal setelah paket/add-on berubah.';
    }
  }

  String _formatBookingTime(String? value) {
    return DateUtil.formatBookingTime(value);
  }

  @override
  void dispose() {
    _disposed = true;
    super.dispose();
  }

  void safeNotify() {
    if (!_disposed) {
      notifyListeners();
    }
  }
}
