import 'dart:typed_data';

import 'package:desktop_flutter/shared/models/transaction_record.dart';
import 'package:pdf/pdf.dart';
import 'package:pdf/widgets.dart' as pw;
import 'package:printing/printing.dart';

class ReceiptPrinter {
  static Future<void> printTransactionReceipt({
    required TransactionRecord transaction,
    required String brandName,
    required String branchName,
    required String cashierName,
    int? paperWidthMm,
  }) async {
    final bytes = await buildTransactionReceiptPdf(
      transaction: transaction,
      brandName: brandName,
      branchName: branchName,
      cashierName: cashierName,
      paperWidthMm: paperWidthMm,
    );

    await Printing.layoutPdf(
      name: 'receipt-${transaction.transactionCode}.pdf',
      onLayout: (_) async => bytes,
    );
  }

  static Future<Uint8List> buildTransactionReceiptPdf({
    required TransactionRecord transaction,
    required String brandName,
    required String branchName,
    required String cashierName,
    int? paperWidthMm,
  }) async {
    final doc = pw.Document();
    final createdAt = _formatDateTime(transaction.createdAt);
    final pageFormat = _pageFormatFromPaperWidth(paperWidthMm);

    doc.addPage(
      pw.MultiPage(
        pageFormat: pageFormat,
        margin: const pw.EdgeInsets.all(14),
        build: (context) => <pw.Widget>[
          pw.Center(
            child: pw.Text(
              brandName,
              style: pw.TextStyle(fontSize: 14, fontWeight: pw.FontWeight.bold),
            ),
          ),
          pw.SizedBox(height: 2),
          pw.Center(
            child: pw.Text(
              branchName,
              style: const pw.TextStyle(fontSize: 10),
              textAlign: pw.TextAlign.center,
            ),
          ),
          pw.SizedBox(height: 10),
          _labelValue('No', transaction.transactionCode),
          _labelValue('Tanggal', createdAt),
          _labelValue('Kasir', cashierName),
          _labelValue('Status', transaction.status.toUpperCase()),
          pw.SizedBox(height: 8),
          pw.Divider(thickness: 0.7),
          pw.SizedBox(height: 4),
          pw.Text(
            'Items',
            style: pw.TextStyle(fontWeight: pw.FontWeight.bold, fontSize: 10),
          ),
          pw.SizedBox(height: 4),
          ...transaction.items.map((item) {
            return pw.Padding(
              padding: const pw.EdgeInsets.only(bottom: 4),
              child: pw.Column(
                crossAxisAlignment: pw.CrossAxisAlignment.start,
                children: <pw.Widget>[
                  pw.Text(
                    item.itemName,
                    style: const pw.TextStyle(fontSize: 10),
                  ),
                  pw.Row(
                    mainAxisAlignment: pw.MainAxisAlignment.spaceBetween,
                    children: <pw.Widget>[
                      pw.Text(
                        '${_formatQty(item.qty)} x ${_currency(item.unitPrice)}',
                        style: const pw.TextStyle(fontSize: 9),
                      ),
                      pw.Text(
                        _currency(item.lineTotal),
                        style: const pw.TextStyle(fontSize: 9),
                      ),
                    ],
                  ),
                ],
              ),
            );
          }),
          pw.Divider(thickness: 0.7),
          _labelValue('Total', _currency(transaction.totalAmount), bold: true),
          _labelValue('Dibayar', _currency(transaction.paidAmount)),
          _labelValue('Kembalian', _currency(transaction.changeAmount)),
          pw.SizedBox(height: 8),
          pw.Text(
            'Pembayaran',
            style: pw.TextStyle(fontWeight: pw.FontWeight.bold, fontSize: 10),
          ),
          pw.SizedBox(height: 4),
          if (transaction.payments.isEmpty)
            pw.Text('-', style: const pw.TextStyle(fontSize: 9))
          else
            ...transaction.payments.map((payment) {
              final ref =
                  payment.referenceNo == null || payment.referenceNo!.isEmpty
                  ? ''
                  : ' (${payment.referenceNo})';

              return _labelValue(
                payment.method.toUpperCase(),
                '${_currency(payment.amount)}$ref',
              );
            }),
          pw.SizedBox(height: 10),
          pw.Divider(thickness: 0.7),
          pw.Center(
            child: pw.Text(
              'Terima kasih',
              style: pw.TextStyle(fontWeight: pw.FontWeight.bold, fontSize: 10),
            ),
          ),
          pw.Center(
            child: pw.Text(
              'Simpan struk ini sebagai bukti pembayaran.',
              style: const pw.TextStyle(fontSize: 8),
              textAlign: pw.TextAlign.center,
            ),
          ),
        ],
      ),
    );

    return doc.save();
  }

  static PdfPageFormat _pageFormatFromPaperWidth(int? paperWidthMm) {
    final width = paperWidthMm ?? 80;

    if (width <= 0) {
      return PdfPageFormat.a6;
    }

    final widthPoint = width * PdfPageFormat.mm;

    return PdfPageFormat(widthPoint, 1000 * PdfPageFormat.mm);
  }

  static pw.Widget _labelValue(
    String label,
    String value, {
    bool bold = false,
  }) {
    return pw.Padding(
      padding: const pw.EdgeInsets.only(bottom: 2),
      child: pw.Row(
        mainAxisAlignment: pw.MainAxisAlignment.spaceBetween,
        children: <pw.Widget>[
          pw.Text(label, style: const pw.TextStyle(fontSize: 9)),
          pw.SizedBox(width: 8),
          pw.Expanded(
            child: pw.Text(
              value,
              style: pw.TextStyle(
                fontSize: 9,
                fontWeight: bold ? pw.FontWeight.bold : pw.FontWeight.normal,
              ),
              textAlign: pw.TextAlign.right,
            ),
          ),
        ],
      ),
    );
  }

  static String _currency(double value) {
    final rounded = value.round();
    final chars = rounded.toString().split('').reversed.toList();
    final buffer = StringBuffer();

    for (var i = 0; i < chars.length; i++) {
      if (i > 0 && i % 3 == 0) {
        buffer.write('.');
      }
      buffer.write(chars[i]);
    }

    return 'Rp ${buffer.toString().split('').reversed.join()}';
  }

  static String _formatQty(double qty) {
    if (qty == qty.roundToDouble()) {
      return qty.toStringAsFixed(0);
    }

    return qty.toStringAsFixed(2);
  }

  static String _formatDateTime(String? value) {
    if (value == null || value.isEmpty) {
      return '-';
    }

    final parsed = DateTime.tryParse(value);

    if (parsed == null) {
      return value;
    }

    final local = parsed.toLocal();
    final year = local.year.toString().padLeft(4, '0');
    final month = local.month.toString().padLeft(2, '0');
    final day = local.day.toString().padLeft(2, '0');
    final hour = local.hour.toString().padLeft(2, '0');
    final minute = local.minute.toString().padLeft(2, '0');

    return '$year-$month-$day $hour:$minute';
  }
}
