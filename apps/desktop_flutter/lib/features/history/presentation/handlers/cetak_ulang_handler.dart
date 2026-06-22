import 'package:flutter/material.dart';
import 'package:desktop_flutter/core/session/api_session.dart';
import 'package:desktop_flutter/features/kasir/services/receipt_printer.dart';
import '../../application/history_controller.dart';
import '../../domain/entities/transaction.dart';

Future<void> handleCetakUlang({
  required BuildContext context,
  required HistoryController controller,
  required Transaction transaction,
}) async {
  if (transaction.backendId <= 0) {
    _snack(context, 'Data transaksi tidak valid.');
    return;
  }

  var busyOpen = false;
  void openBusy() {
    busyOpen = true;
    showDialog(
      context: context,
      barrierDismissible: false,
      builder: (_) => const Center(
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

  openBusy();

  try {
    final record = await controller.reprintTransaction(transaction);

    if (!context.mounted) return;
    closeBusy();

    if (record == null) {
      _snack(context, 'Data transaksi tidak ditemukan untuk cetak ulang.');
      return;
    }

    await ReceiptPrinter.printTransactionReceipt(
      transaction: record,
      brandName: 'Ready To Pict',
      branchName: record.branchName.isNotEmpty
          ? record.branchName
          : transaction.branchName,
      cashierName: ApiSession.current?.user.name ?? '-',
      receiptTitle: 'STRUK',
      paperWidthMm: 80,
    );
    _snack(context, 'Cetak ulang berhasil.');
  } catch (error) {
    if (context.mounted) closeBusy();
    _snack(context, 'Cetak ulang gagal: Periksa printer.');
  }
}

void _snack(BuildContext context, String message) {
  if (!context.mounted) return;
  ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text(message)));
}
