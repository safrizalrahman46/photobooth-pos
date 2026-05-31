import 'dart:typed_data';

import 'package:desktop_flutter/shared/models/cashier_settlement_item.dart';
import 'package:desktop_flutter/shared/models/transaction_record.dart';
import 'package:pdf/pdf.dart';
import 'package:pdf/widgets.dart' as pw;
import 'package:printing/printing.dart';

class ReceiptPrinter {
  static Future<void> printCashierSettlementReceipt({
    required CashierSettlementItem settlement,
    int? paperWidthMm,
  }) async {
    final bytes = await buildCashierSettlementReceiptPdf(
      settlement: settlement,
      paperWidthMm: paperWidthMm,
    );

    await Printing.layoutPdf(
      name: 'settlement-${settlement.settlementCode}.pdf',
      onLayout: (_) async => bytes,
    );
  }

  static Future<Uint8List> buildCashierSettlementReceiptPdf({
    required CashierSettlementItem settlement,
    int? paperWidthMm,
  }) async {
    final doc = pw.Document();
    final snapshot = settlement.snapshot;
    final summary = _mapAt(snapshot, 'summary');
    final nonCashRows = _listAt(snapshot, 'non_cash');
    final packageRows = _listAt(snapshot, 'package_sales');
    final dpRows = _listAt(snapshot, 'dp_info');
    final expenseRows = _listAt(snapshot, 'expenses');
    final notes = _listAt(snapshot, 'notes');

    doc.addPage(
      pw.MultiPage(
        pageFormat: _pageFormatFromPaperWidth(paperWidthMm),
        margin: const pw.EdgeInsets.all(14),
        build: (context) => <pw.Widget>[
          if (settlement.printCount > 1) ...[
            pw.Center(
              child: pw.Text(
                'CETAK ULANG #${settlement.printCount}',
                style: pw.TextStyle(
                  fontSize: 11,
                  fontWeight: pw.FontWeight.bold,
                ),
              ),
            ),
            pw.SizedBox(height: 4),
          ],
          pw.Center(
            child: pw.Text(
              'LAPORAN',
              style: pw.TextStyle(fontSize: 12, fontWeight: pw.FontWeight.bold),
            ),
          ),
          pw.Center(
            child: pw.Text(
              'REKAP SETORAN PER KASIR',
              style: pw.TextStyle(fontSize: 10, fontWeight: pw.FontWeight.bold),
            ),
          ),
          pw.SizedBox(height: 8),
          _labelValue('No Setoran', settlement.settlementCode),
          _labelValue('Periode', settlement.periodLabel),
          _labelValue('Kasir', settlement.cashierName),
          _labelValue('Cabang', settlement.branchName),
          pw.SizedBox(height: 6),
          pw.Divider(thickness: 0.7),
          _sectionTitle('RINGKASAN SETORAN'),
          _labelValue('Total Penjualan', _stringAt(summary, 'total_sales_text')),
          _labelValue('Cash Diterima', _stringAt(summary, 'cash_received_text')),
          _labelValue('Non Cash', _stringAt(summary, 'non_cash_received_text')),
          _labelValue(
            'Pengeluaran Cash',
            _stringAt(summary, 'cash_expenses_total_text'),
          ),
          pw.Divider(thickness: 0.7),
          _labelValue(
            'JML. DISETOR CASH',
            _stringAt(summary, 'cash_to_deposit_text'),
            bold: true,
          ),
          _labelValue(
            'Uang Laci Disisakan',
            _stringAt(summary, 'opening_cash_text'),
          ),
          pw.SizedBox(height: 6),
          _sectionTitle('NON CASH'),
          ...nonCashRows.map(
            (row) => _labelValue(
              _stringAt(row, 'method'),
              _stringAt(row, 'amount_text'),
            ),
          ),
          pw.SizedBox(height: 6),
          _sectionTitle('PENJUALAN PER PAKET'),
          if (packageRows.isEmpty)
            _labelValue('-', _currency(0))
          else
            ...packageRows.map(
              (row) => _labelValue(
                _stringAt(row, 'package_name'),
                _stringAt(row, 'amount_text'),
              ),
            ),
          pw.SizedBox(height: 6),
          _sectionTitle('INFO DP'),
          ...dpRows.map(
            (row) => _labelValue(
              _stringAt(row, 'label'),
              _stringAt(row, 'amount_text'),
            ),
          ),
          pw.SizedBox(height: 6),
          _sectionTitle('DETAIL PENGELUARAN'),
          if (expenseRows.isEmpty)
            _labelValue('-', _currency(0))
          else
            ...expenseRows.map(
              (row) => _labelValue(
                _stringAt(row, 'title'),
                _stringAt(row, 'amount_text'),
              ),
            ),
          if (notes.isNotEmpty) ...[
            pw.SizedBox(height: 8),
            pw.Divider(thickness: 0.7),
            ...notes.map(
              (note) => pw.Text(
                note.toString(),
                style: const pw.TextStyle(fontSize: 8),
              ),
            ),
          ],
          pw.SizedBox(height: 18),
          pw.Row(
            mainAxisAlignment: pw.MainAxisAlignment.spaceBetween,
            children: <pw.Widget>[
              pw.Text('Kasir: __________', style: const pw.TextStyle(fontSize: 9)),
              pw.Text('Owner: __________', style: const pw.TextStyle(fontSize: 9)),
            ],
          ),
        ],
      ),
    );

    return doc.save();
  }

  static Future<void> printTransactionReceipt({
    required TransactionRecord transaction,
    required String brandName,
    required String branchName,
    required String cashierName,
    String? queueCode,
    String? receiptTitle,
    int? paperWidthMm,
  }) async {
    final bytes = await buildTransactionReceiptPdf(
      transaction: transaction,
      brandName: brandName,
      branchName: branchName,
      cashierName: cashierName,
      queueCode: queueCode,
      receiptTitle: receiptTitle,
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
    String? queueCode,
    String? receiptTitle,
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
          if (receiptTitle != null && receiptTitle.isNotEmpty) ...[
            pw.Center(
              child: pw.Text(
                receiptTitle,
                style: pw.TextStyle(
                  fontSize: 11,
                  fontWeight: pw.FontWeight.bold,
                ),
              ),
            ),
            pw.SizedBox(height: 8),
          ],
          _labelValue('No', transaction.transactionCode),
          if (queueCode != null && queueCode.isNotEmpty)
            _labelValue('Antrean', queueCode),
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
          if (transaction.discountAmount > 0) ...[
            _labelValue('Subtotal', _currency(transaction.subtotalAmount)),
            if (transaction.referralCode.isNotEmpty)
              _labelValue('Referal', transaction.referralCode),
            _labelValue('Diskon', '-${_currency(transaction.discountAmount)}'),
          ],
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

  static pw.Widget _sectionTitle(String title) {
    return pw.Padding(
      padding: const pw.EdgeInsets.only(bottom: 4),
      child: pw.Text(
        title,
        style: pw.TextStyle(fontSize: 9, fontWeight: pw.FontWeight.bold),
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

  static Map<String, dynamic> _mapAt(Map<String, dynamic> source, String key) {
    final value = source[key];

    return value is Map<String, dynamic> ? value : <String, dynamic>{};
  }

  static List<dynamic> _listAt(Map<String, dynamic> source, String key) {
    final value = source[key];

    return value is List ? value : const <dynamic>[];
  }

  static String _stringAt(dynamic source, String key) {
    if (source is Map<String, dynamic>) {
      return source[key]?.toString() ?? '-';
    }

    return '-';
  }
}
