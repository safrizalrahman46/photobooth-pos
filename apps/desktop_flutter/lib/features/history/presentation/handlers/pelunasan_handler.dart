import 'package:flutter/material.dart';
import 'package:desktop_flutter/features/kasir/services/receipt_printer.dart';
import '../../application/history_controller.dart';
import '../../domain/entities/transaction.dart';
import '../widgets/dialogs/pelunasan_dialog.dart';

Future<void> handlePelunasan({
  required BuildContext context,
  required HistoryController controller,
  required Transaction transaction,
}) async {
  if (transaction.backendId <= 0) {
    _snack(context, 'Data transaksi tidak valid.');
    return;
  }

  final result = await showDialog<PelunasanDialogResult>(
    context: context,
    builder: (_) => PelunasanDialog(transaction: transaction),
  );

  if (result == null) return;

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
    final record = await controller.pelunasanPayment(
      transactionId: transaction.backendId,
      method: result.method,
      amount: result.amount,
      referenceNo: result.referenceNo,
    );

    if (!context.mounted) return;
    closeBusy();

    if (record == null) {
      _snack(context, controller.errorMessage ?? 'Pelunasan belum berhasil.');
      return;
    }

    try {
      await ReceiptPrinter.printTransactionReceipt(
        transaction: record,
        brandName: 'Ready To Pict',
        branchName: record.branchName.isNotEmpty
            ? record.branchName
            : transaction.branchName,
        cashierName: 'Kasir',
        receiptTitle: 'STRUK PELUNASAN',
        paperWidthMm: 80,
      );
      _snack(context, 'Pelunasan berhasil dan struk siap dicetak.');
    } catch (_) {
      _snack(context, 'Pelunasan berhasil, tetapi struk gagal dicetak.');
    }
  } catch (error) {
    if (context.mounted) closeBusy();
    _snack(context, 'Pelunasan gagal diproses.');
  }
}

void _snack(BuildContext context, String message) {
  if (!context.mounted) return;
  ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text(message)));
}
