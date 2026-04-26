// features/history/presentation/widgets/history/history_table.dart

import 'package:flutter/material.dart';
import '../../../domain/entities/transaction.dart';
import 'history_row.dart';
import 'history_empty.dart';

// ─── Referensi global ──────────────────────────────────────────────────────────
// Gunakan AppCard dari shared/widgets/common/app_card.dart sebagai container
// agar shadow & border radius konsisten di seluruh aplikasi.
// Contoh: import '../../../../../shared/widgets/common/app_card.dart';

/// Tabel lengkap dengan header kolom + baris-baris data transaksi.
///
/// Menampilkan [HistoryEmpty] otomatis ketika [transactions] kosong.
///
/// Contoh penggunaan:
/// ```dart
/// HistoryTable(
///   transactions: controller.pagedTransactions,
///   onRowAction: controller.onRowAction,
/// )
/// ```
class HistoryTable extends StatelessWidget {
  final List<Transaction> transactions;
  final void Function(Transaction) onRowAction;

  // Lebar kolom — harus sinkron dengan HistoryRow
  // Flex factors untuk masing-masing kolom agar proporsional
  static const int _flexId = 2;
  static const int _flexWaktu = 2;
  static const int _flexNama = 3;
  static const int _flexPaket = 4;
  static const int _flexTotal = 2;
  static const int _flexStatus = 2;
  static const double _colAction = 48; // Action tetap kecil kinetik

  const HistoryTable({
    super.key,
    required this.transactions,
    required this.onRowAction,
  });

  @override
  Widget build(BuildContext context) {
    return Container(
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.circular(12),
        // Jika menggunakan AppCard global, ganti Container ini dengan AppCard
        boxShadow: [
          BoxShadow(
            color: Colors.black.withOpacity(0.04),
            blurRadius: 8,
            offset: const Offset(0, 2),
          ),
        ],
      ),
      child: Column(
        children: [
          _buildHeader(),
          if (transactions.isEmpty)
            const HistoryEmpty()
          else
            ...transactions.map(
              (tx) => HistoryRow(
                transaction: tx,
                onActionPressed: () => onRowAction(tx),
              ),
            ),
        ],
      ),
    );
  }

  Widget _buildHeader() {
    return Container(
      decoration: const BoxDecoration(
        color: Color(0xFFF9FAFB), // gray-50
        borderRadius: BorderRadius.only(
          topLeft: Radius.circular(12),
          topRight: Radius.circular(12),
        ),
        border: Border(bottom: BorderSide(color: Color(0xFFE5E7EB))),
      ),
      padding: const EdgeInsets.symmetric(vertical: 12, horizontal: 16),
      child: Row(
        children: [
          _headerCell('ID TRANSAKSI', _flexId),
          _headerCell('WAKTU', _flexWaktu),
          _headerCell('NAMA PELANGGAN', _flexNama),
          _headerCell('PAKET & ADD-ONS', _flexPaket),
          _headerCell('TOTAL BAYAR', _flexTotal),
          _headerCell('STATUS', _flexStatus),
          const SizedBox(width: _colAction), // spacer untuk kolom action
        ],
      ),
    );
  }

  Widget _headerCell(String label, int flex) {
    return Expanded(
      flex: flex,
      child: Text(
        label,
        style: const TextStyle(
          fontSize: 12,
          fontWeight: FontWeight.w700,
          color: Color(0xFF6B7280), // gray-500
          letterSpacing: 0.5,
        ),
      ),
    );
  }
}
