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
  static const double _colId = 130;
  static const double _colWaktu = 100;
  static const double _colNama = 140;
  static const double _colPaket = 160;
  static const double _colTotal = 110;
  static const double _colStatus = 110;
  static const double _colAction = 48;

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
          _headerCell('ID TRANSAKSI', _colId),
          _headerCell('WAKTU', _colWaktu),
          _headerCell('NAMA\nPELANGGAN', _colNama),
          _headerCell('PAKET &\nADD-ONS', _colPaket),
          _headerCell('TOTAL\nBAYAR', _colTotal),
          _headerCell('STATUS', _colStatus),
          SizedBox(width: _colAction), // spacer untuk kolom action
        ],
      ),
    );
  }

  Widget _headerCell(String label, double width) {
    return SizedBox(
      width: width,
      child: Text(
        label,
        style: const TextStyle(
          fontSize: 11,
          fontWeight: FontWeight.w600,
          color: Color(0xFF9CA3AF), // gray-400
          letterSpacing: 0.5,
          height: 1.4,
        ),
      ),
    );
  }
}
