import 'package:flutter/material.dart';
import 'package:desktop_flutter/app/theme/app_colors.dart';
import 'package:desktop_flutter/core/network/request_error_message.dart';
import 'package:desktop_flutter/core/session/api_session.dart';
import 'package:desktop_flutter/features/kasir/services/receipt_printer.dart';
import 'package:desktop_flutter/shared/models/add_on_catalog_item.dart';
import '../widgets/dialogs/busy_dialog.dart';
import '../widgets/dialogs/extra_print_dialog.dart';
import '../widgets/dialogs/booking_payoff_dialog.dart';
import '../../application/history_controller.dart';
import '../../domain/entities/transaction.dart' as history_domain;
import '../sections/history_header_section.dart';
import '../sections/history_table_section.dart';
import '../sections/history_pagination_section.dart';

class HistoryPage extends StatefulWidget {
  const HistoryPage({super.key});

  @override
  State<HistoryPage> createState() => _HistoryPageState();
}

class _HistoryPageState extends State<HistoryPage> {
  late final HistoryController _controller;

  @override
  void initState() {
    super.initState();
    _controller = HistoryController();
    _controller.addListener(_onControllerUpdate);
  }

  void _onControllerUpdate() {
    if (mounted) setState(() {});
  }

  @override
  void dispose() {
    _controller.removeListener(_onControllerUpdate);
    _controller.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    return Container(
      color: const Color(0xFFF8FAFC),
      child: SingleChildScrollView(
        padding: const EdgeInsets.fromLTRB(40, 40, 40, 32),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            // Premium Solid Blue Header Card
            Container(
              width: double.infinity,
              padding: const EdgeInsets.all(28),
              decoration: BoxDecoration(
                color: AppColors.primary,
                borderRadius: BorderRadius.circular(24),
                boxShadow: [
                  BoxShadow(
                    color: AppColors.primary.withValues(alpha: 0.15),
                    blurRadius: 16,
                    offset: const Offset(0, 8),
                  ),
                ],
              ),
              child: Row(
                children: [
                  Container(
                    padding: const EdgeInsets.all(16),
                    decoration: BoxDecoration(
                      color: Colors.white.withValues(alpha: 0.12),
                      borderRadius: BorderRadius.circular(16),
                      border: Border.all(color: Colors.white.withValues(alpha: 0.2)),
                    ),
                    child: const Icon(Icons.receipt_long_rounded, color: Colors.white, size: 36),
                  ),
                  const SizedBox(width: 20),
                  const Expanded(
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        Text(
                          'Riwayat Transaksi',
                          style: TextStyle(
                            fontSize: 26,
                            fontWeight: FontWeight.w900,
                            color: Colors.white,
                            letterSpacing: 0.5,
                          ),
                        ),
                        SizedBox(height: 6),
                        Text(
                          'Kelola, filter, dan tinjau seluruh riwayat transaksi photobooth serta lakukan tambah cetak add-on.',
                          style: TextStyle(color: Colors.white70, fontSize: 13, height: 1.4),
                        ),
                      ],
                    ),
                  ),
                ],
              ),
            ),
            const SizedBox(height: 24),

            // Header (Integrated Logic)
            HistoryHeaderSection(
              searchQuery: _controller.searchQuery,
              onSearchChanged: _controller.onSearchChanged,
              selectedStatus: _controller.statusFilter,
              onStatusFilterChanged: _controller.onStatusFilterChanged,
              onExport: _controller.onExport,
            ),

            const SizedBox(height: 24),

            if (_controller.errorMessage != null) ...[
              Text(
                _controller.errorMessage!,
                style: const TextStyle(color: Colors.redAccent),
              ),
              const SizedBox(height: 16),
            ],

            // Table Premium with logic
            if (_controller.isLoading)
              const Center(child: CircularProgressIndicator())
            else
              HistoryTableSection(
                transactions: _controller.pagedTransactions,
                onRowAction: (transaction) {
                  if (transaction.status == history_domain.TransactionStatus.pending) {
                    _handleBookingPayoff(transaction);
                  } else {
                    _handleExtraPrint(transaction);
                  }
                },
              ),

            const SizedBox(height: 32),

            // Pagination Logic
            HistoryPaginationSection(
              currentPage: _controller.currentPage,
              totalPages: _controller.totalPages,
              paginationLabel: _controller.paginationLabel,
              onPageChanged: _controller.goToPage,
              onPrev: _controller.prevPage,
              onNext: _controller.nextPage,
            ),
          ],
        ),
      ),
    );
  }

  Future<void> _handleExtraPrint(history_domain.Transaction transaction) async {
    if (transaction.backendId <= 0) {
      _showSnack('Data transaksi tidak valid untuk tambah cetak.');
      return;
    }

    if (transaction.status != history_domain.TransactionStatus.lunas) {
      _showSnack('Tambah cetak hanya untuk transaksi yang sudah lunas.');
      return;
    }

    final client = ApiSession.client;

    if (client == null) {
      _showSnack('Sesi kasir tidak aktif. Silakan login ulang.');
      return;
    }

    var busyOpen = false;

    void openBusy(String message) {
      busyOpen = true;
      showDialog(
        context: context,
        barrierDismissible: false,
        builder: (context) => BusyDialog(message: message),
      );
    }

    void closeBusy() {
      if (busyOpen && mounted) {
        Navigator.of(context, rootNavigator: true).pop();
      }
      busyOpen = false;
    }

    openBusy('Memuat opsi tambah cetak...');

    try {
      final options = await _loadExtraPrintOptions(transaction);

      if (!mounted) return;
      closeBusy();

      if (options.isEmpty) {
        _showSnack('Belum ada add-on aktif yang tersedia untuk transaksi ini.');
        return;
      }

      final result = await showDialog<ExtraPrintDialogResult>(
        context: context,
        builder: (context) =>
            ExtraPrintDialog(transaction: transaction, addOns: options),
      );

      if (result == null) {
        return;
      }

      final oldItemIds = transaction.items.map((item) => item.id).toSet();

      openBusy('Memproses tambah add-on...');

      final updated = await _controller.addExtraPrint(
        transaction: transaction,
        addOn: result.addOn,
        qty: result.qty,
        paymentMethod: result.paymentMethod,
        referenceNo: result.referenceNo,
      );

      if (!mounted) return;
      closeBusy();

      if (updated == null) {
        _showSnack(_controller.errorMessage ?? 'Tambah cetak belum berhasil.');
        return;
      }

      try {
        final newlyAddedItems = updated.items
            .where(
              (item) =>
                  item.id > 0 &&
                  item.itemType == 'add_on' &&
                  item.itemRefId == result.addOn.id &&
                  !oldItemIds.contains(item.id),
            )
            .toList();
        final fallbackItems = newlyAddedItems.isEmpty
            ? updated.items.reversed
                  .where(
                    (item) =>
                        item.id > 0 &&
                        item.itemType == 'add_on' &&
                        item.itemRefId == result.addOn.id,
                  )
                  .take(1)
            : newlyAddedItems;
        final highlightedItemIds = fallbackItems
            .map((item) => item.id)
            .toSet();

        await ReceiptPrinter.printTransactionReceipt(
          transaction: updated,
          brandName: 'Ready To Pict',
          branchName: updated.branchName.isNotEmpty
              ? updated.branchName
              : transaction.branchName,
          cashierName: ApiSession.current?.user.name ?? '-',
          receiptTitle: 'STRUK GABUNGAN',
          highlightedItemIds: highlightedItemIds,
          paperWidthMm: 80,
        );
        _showSnack('Tambah add-on berhasil dan struk gabungan siap dicetak.');
      } catch (error) {
        _showSnack(
          'Tambah add-on berhasil, tetapi struk belum tercetak: ${resolveRequestErrorMessage(error, fallback: 'Periksa printer.')}',
        );
      }
    } catch (error) {
      if (!mounted) return;
      closeBusy();
      _showSnack(
        resolveRequestErrorMessage(
          error,
          fallback: 'Tambah cetak belum dapat diproses.',
        ),
      );
    }
  }

  Future<void> _handleBookingPayoff(history_domain.Transaction transaction) async {
    if (transaction.bookingId == null) {
      _showSnack('Transaksi pending ini tidak terhubung dengan data booking.');
      return;
    }

    final client = ApiSession.client;

    if (client == null) {
      _showSnack('Sesi kasir tidak aktif. Silakan login ulang.');
      return;
    }

    final result = await showDialog<BookingPayoffResult>(
      context: context,
      builder: (context) => BookingPayoffDialog(transaction: transaction),
    );

    if (result == null) {
      return;
    }

    var busyOpen = false;

    void openBusy(String message) {
      busyOpen = true;
      showDialog(
        context: context,
        barrierDismissible: false,
        builder: (context) => BusyDialog(message: message),
      );
    }

    void closeBusy() {
      if (busyOpen && mounted) {
        Navigator.of(context, rootNavigator: true).pop();
      }
      busyOpen = false;
    }

    openBusy('Memproses pelunasan booking...');

    try {
      final updatedBooking = await client.confirmBookingPayment(
        bookingId: transaction.bookingId!,
        amount: transaction.sisaBayar.toDouble(),
        method: result.paymentMethod,
        referenceNo: result.paymentMethod == 'cash' ? null : '',
      );

      final updatedTransaction = await client.fetchTransactionDetail(
        transactionId: transaction.backendId,
      );

      if (!mounted) return;
      closeBusy();

      // Print final receipt
      try {
        await ReceiptPrinter.printTransactionReceipt(
          transaction: updatedTransaction,
          brandName: 'Ready To Pict',
          branchName: updatedTransaction.branchName.isNotEmpty
              ? updatedTransaction.branchName
              : transaction.branchName,
          cashierName: ApiSession.current?.user.name ?? '-',
          receiptTitle: 'STRUK PELUNASAN BOOKING',
          paperWidthMm: 80,
        );
      } catch (error) {
        _showSnack(
          'Pelunasan berhasil, tetapi struk belum tercetak: ${resolveRequestErrorMessage(error, fallback: 'Periksa printer.')}',
        );
      }

      _showSnack('Pelunasan booking ${updatedBooking.bookingCode} berhasil dikonfirmasi.');
      await _controller.loadTransactions();
    } catch (error) {
      if (!mounted) return;
      closeBusy();
      _showSnack(
        resolveRequestErrorMessage(
          error,
          fallback: 'Konfirmasi pelunasan belum dapat diproses.',
        ),
      );
    }
  }

  Future<List<AddOnCatalogItem>> _loadExtraPrintOptions(
    history_domain.Transaction transaction,
  ) async {
    final client = ApiSession.client;

    if (client == null) {
      return <AddOnCatalogItem>[];
    }

    final rows = await client.fetchAddOns(
      packageId: transaction.packageId,
      perPage: 200,
    );
    final available = rows
        .where(
          (item) =>
              item.isActive &&
              (item.effectiveAvailableStock == null ||
                  item.effectiveAvailableStock! > 0),
        )
        .toList();
    return available;
  }

  void _showSnack(String message) {
    if (!mounted) return;

    ScaffoldMessenger.of(
      context,
    ).showSnackBar(SnackBar(content: Text(message)));
  }
}
