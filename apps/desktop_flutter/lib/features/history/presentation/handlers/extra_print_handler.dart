import 'package:flutter/material.dart';
import 'package:desktop_flutter/core/session/api_session.dart';
import 'package:desktop_flutter/core/network/request_error_message.dart';
import 'package:desktop_flutter/features/kasir/services/receipt_printer.dart';
import 'package:desktop_flutter/shared/models/add_on_catalog_item.dart';
import '../../application/history_controller.dart';
import '../../domain/entities/transaction.dart';
import '../widgets/dialogs/extra_print_dialog.dart';

Future<void> handleExtraPrint({
  required BuildContext context,
  required HistoryController controller,
  required Transaction transaction,
}) async {
  if (transaction.backendId <= 0) {
    _snack(context, 'Data transaksi tidak valid untuk tambah cetak.');
    return;
  }

  if (transaction.status != TransactionStatus.lunas) {
    _snack(context, 'Tambah cetak hanya untuk transaksi yang sudah lunas.');
    return;
  }

  final client = ApiSession.client;
  if (client == null) {
    _snack(context, 'Sesi kasir tidak aktif. Silakan login ulang.');
    return;
  }

  var busyOpen = false;
  void openBusy(String message) {
    busyOpen = true;
    showDialog(
      context: context,
      barrierDismissible: false,
      builder: (context) => const Center(
        child: Material(
          color: Colors.transparent,
          child: CircularProgressIndicator(),
        ),
      ),
    );
  }

  void closeBusy() {
    if (busyOpen && context.mounted) {
      Navigator.of(context, rootNavigator: true).pop();
    }
    busyOpen = false;
  }

  openBusy('Memuat opsi tambah cetak...');

  try {
    final options = await _loadExtraPrintOptions(transaction);

    if (!context.mounted) return;
    closeBusy();

    if (options.isEmpty) {
      _snack(context, 'Belum ada add-on aktif yang tersedia.');
      return;
    }

    final result = await showDialog<ExtraPrintDialogResult>(
      context: context,
      builder: (_) => ExtraPrintDialog(transaction: transaction, addOns: options),
    );

    if (result == null) return;

    final oldItemIds = transaction.items.map((item) => item.id).toSet();

    openBusy('Memproses tambah add-on...');

    final updated = await controller.addExtraPrintBulk(
      transactionId: transaction.backendId,
      items: result.items
          .map((e) => {'add_on_id': e.addOn.id, 'qty': e.qty})
          .toList(),
      paymentMethod: result.paymentMethod,
      referenceNo: result.referenceNo,
    );

    if (!context.mounted) return;
    closeBusy();

    if (updated == null) {
      _snack(context, controller.errorMessage ?? 'Tambah cetak belum berhasil.');
      return;
    }

    try {
      final highlightedItemIds = <int>{};
      for (final item in result.items) {
        final matched = updated.items.where(
          (i) =>
              i.id > 0 &&
              i.itemType == 'add_on' &&
              i.itemRefId == item.addOn.id &&
              !oldItemIds.contains(i.id),
        );
        if (matched.isNotEmpty) {
          highlightedItemIds.add(matched.first.id);
        }
      }

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
      _snack(context, 'Tambah add-on berhasil dan struk gabungan siap dicetak.');
    } catch (error) {
      _snack(
        context,
        'Tambah add-on berhasil, tetapi struk belum tercetak: ${resolveRequestErrorMessage(error, fallback: 'Periksa printer.')}',
      );
    }
  } catch (error) {
    if (context.mounted) closeBusy();
    _snack(
      context,
      resolveRequestErrorMessage(error, fallback: 'Tambah cetak belum dapat diproses.'),
    );
  }
}

Future<List<AddOnCatalogItem>> _loadExtraPrintOptions(
  Transaction transaction,
) async {
  final client = ApiSession.client;
  if (client == null) return [];
  final rows = await client.fetchAddOns(
    packageId: transaction.packageId,
    perPage: 200,
  );
  return rows
      .where(
        (item) =>
            item.isActive &&
            (item.effectiveAvailableStock == null ||
                item.effectiveAvailableStock! > 0),
      )
      .toList();
}

void _snack(BuildContext context, String message) {
  if (!context.mounted) return;
  ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text(message)));
}
