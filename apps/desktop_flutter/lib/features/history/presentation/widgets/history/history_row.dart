// features/history/presentation/widgets/history/history_row.dart

import 'package:flutter/material.dart';
import 'package:intl/intl.dart';
import '../../../domain/entities/transaction.dart';
import '../common/transaction_status_badge.dart';

// ─── Referensi global ──────────────────────────────────────────────────────────
// Gunakan AppTextStyles dari app/theme/app_text_styles.dart untuk konsistensi teks.
// Contoh: import '../../../../../app/theme/app_text_styles.dart';

/// Satu baris data transaksi dalam tabel History.
///
/// Menerima [transaction] dan callback [onActionPressed] untuk
/// menu titik tiga (⋮) di ujung kanan.
///
/// Contoh penggunaan:
/// ```dart
/// HistoryRow(
///   transaction: tx,
///   onActionPressed: () => controller.onRowAction(tx),
/// )
/// ```
class HistoryRow extends StatelessWidget {
  final Transaction transaction;
  final VoidCallback onActionPressed;

  // Lebar kolom — harus sinkron dengan HistoryTable header
  static const double _colId = 130;
  static const double _colWaktu = 100;
  static const double _colNama = 140;
  static const double _colPaket = 160;
  static const double _colTotal = 110;
  static const double _colStatus = 110;
  static const double _colAction = 48;

  const HistoryRow({
    super.key,
    required this.transaction,
    required this.onActionPressed,
  });

  @override
  Widget build(BuildContext context) {
    return Container(
      decoration: const BoxDecoration(
        border: Border(
          bottom: BorderSide(color: Color(0xFFF3F4F6)), // gray-100
        ),
      ),
      child: Padding(
        padding: const EdgeInsets.symmetric(vertical: 18, horizontal: 16),
        child: Row(
          children: [
            // ID Transaksi
            SizedBox(
              width: _colId,
              child: Text(
                transaction.id,
                style: const TextStyle(
                  fontSize: 14,
                  fontWeight: FontWeight.w700,
                  color: Color(0xFF111827), // gray-900
                ),
              ),
            ),

            // Waktu
            SizedBox(
              width: _colWaktu,
              child: Text(
                _formatWaktu(transaction.waktu),
                style: const TextStyle(
                  fontSize: 13,
                  color: Color(0xFF6B7280), // gray-500
                  height: 1.5,
                ),
              ),
            ),

            // Nama Pelanggan
            SizedBox(
              width: _colNama,
              child: Text(
                transaction.namaPelanggan,
                style: const TextStyle(
                  fontSize: 14,
                  color: Color(0xFF374151), // gray-700
                ),
              ),
            ),

            // Paket & Add-ons
            SizedBox(
              width: _colPaket,
              child: Text(
                transaction.paketDanAddOns,
                style: const TextStyle(
                  fontSize: 13,
                  color: Color(0xFF6B7280),
                  height: 1.4,
                ),
              ),
            ),

            // Total Bayar
            SizedBox(
              width: _colTotal,
              child: Text(
                _formatRupiah(transaction.totalBayar),
                style: const TextStyle(
                  fontSize: 14,
                  fontWeight: FontWeight.w700,
                  color: Color(0xFF111827),
                ),
              ),
            ),

            // Status
            SizedBox(
              width: _colStatus,
              child: TransactionStatusBadge(status: transaction.status),
            ),

            // Action menu (⋮)
            SizedBox(
              width: _colAction,
              child: IconButton(
                onPressed: onActionPressed,
                icon: const Icon(Icons.more_vert_rounded),
                iconSize: 20,
                color: const Color(0xFF9CA3AF), // gray-400
                padding: EdgeInsets.zero,
                constraints: const BoxConstraints(),
                splashRadius: 16,
              ),
            ),
          ],
        ),
      ),
    );
  }

  /// Format: "24\nOct,\n14:22"
  String _formatWaktu(DateTime dt) {
    final day = dt.day.toString();
    final month = DateFormat('MMM').format(dt); // "Oct"
    final time = DateFormat('HH:mm').format(dt);
    return '$day\n$month,\n$time';
  }

  /// Format: "Rp\n90.000"  (ditampilkan dalam satu Text dengan newline)
  String _formatRupiah(int amount) {
    final formatted = NumberFormat('#,###', 'id_ID').format(amount);
    return 'Rp\n$formatted';
  }
}
