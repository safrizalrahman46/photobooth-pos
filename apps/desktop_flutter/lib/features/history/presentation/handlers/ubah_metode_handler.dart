import 'package:flutter/material.dart';
import 'package:desktop_flutter/core/session/api_session.dart';
import '../../application/history_controller.dart';
import '../../domain/entities/transaction.dart';
import '../widgets/dialogs/ubah_metode_dialog.dart';

Future<void> handleUbahMetode({
  required BuildContext context,
  required HistoryController controller,
  required Transaction transaction,
}) async {
  final client = ApiSession.client;
  if (client == null) {
    _snack(context, 'Sesi tidak aktif.');
    return;
  }

  if (transaction.rawRecord == null) {
    _snack(context, 'Data transaksi tidak lengkap.');
    return;
  }

  final latestPayment = transaction.rawRecord!.payments.isNotEmpty
      ? transaction.rawRecord!.payments.last
      : null;

  if (latestPayment == null) {
    _snack(context, 'Tidak ada pembayaran untuk diubah.');
    return;
  }

  final result = await showDialog<UbahMetodeDialogResult>(
    context: context,
    builder: (_) => UbahMetodeDialog(
      currentMethod: latestPayment.method,
      transactionCode: transaction.id,
    ),
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
    final success = await client.updatePaymentMethod(
      paymentId: latestPayment.id,
      method: result.method,
      reason: result.reason,
    );

    if (!context.mounted) return;
    closeBusy();

    if (success) {
      await controller.loadTransactions();
      if (context.mounted) {
        _snack(context,
            'Metode pembayaran ${transaction.id} diubah ke ${_methodLabel(result.method)}.');
      }
    } else {
      _snack(context, 'Gagal mengubah metode pembayaran.');
    }
  } catch (_) {
    if (context.mounted) closeBusy();
    _snack(context, 'Terjadi kesalahan saat mengubah metode.');
  }
}

String _methodLabel(String method) {
  switch (method) {
    case 'cash': return 'Tunai';
    case 'qris': return 'QRIS';
    case 'transfer': return 'Transfer';
    case 'card': return 'Kartu';
    default: return method;
  }
}

void _snack(BuildContext context, String message) {
  if (!context.mounted) return;
  ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text(message)));
}
