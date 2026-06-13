import 'package:flutter/material.dart';
import 'package:intl/intl.dart';
import 'package:desktop_flutter/app/theme/app_colors.dart';
import '../../../domain/entities/transaction.dart';
import '../common/transaction_status_badge.dart';

class HistoryRow extends StatelessWidget {
  final Transaction transaction;
  final VoidCallback onActionPressed;

  // Flex factors - harus sama persis dengan HistoryTable
  static const int _flexId = 2;
  static const int _flexWaktu = 3;
  static const int _flexNama = 3;
  static const int _flexPaket = 4;
  static const int _flexTotal = 2;
  static const int _flexStatus = 2;
  static const double _colAction = 150;

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
          bottom: BorderSide(color: Color(0xFFF1F5F9)),
        ),
      ),
      child: Padding(
        padding: const EdgeInsets.symmetric(vertical: 18, horizontal: 24),
        child: Row(
          children: [
            // ID Transaksi
            Expanded(
              flex: _flexId,
              child: Padding(
                padding: const EdgeInsets.only(right: 16),
                child: Text(
                  transaction.id,
                  style: const TextStyle(
                    fontSize: 14,
                    fontWeight: FontWeight.bold,
                    color: Color(0xFF1E293B),
                  ),
                ),
              ),
            ),

            // Waktu
            Expanded(
              flex: _flexWaktu,
              child: Padding(
                padding: const EdgeInsets.only(right: 16),
                child: Text(
                  _formatWaktu(transaction.waktu),
                  style: const TextStyle(
                    fontSize: 13,
                    color: Color(0xFF64748B),
                    height: 1.5,
                  ),
                ),
              ),
            ),

            // Nama Pelanggan
            Expanded(
              flex: _flexNama,
              child: Padding(
                padding: const EdgeInsets.only(right: 16),
                child: Text(
                  transaction.namaPelanggan,
                  style: const TextStyle(
                    fontSize: 14,
                    fontWeight: FontWeight.w600,
                    color: Color(0xFF1E293B),
                  ),
                ),
              ),
            ),

            // Paket & Add-ons
            Expanded(
              flex: _flexPaket,
              child: Padding(
                padding: const EdgeInsets.only(right: 16),
                child: Text(
                  transaction.paketDanAddOns,
                  style: const TextStyle(
                    fontSize: 13,
                    color: Color(0xFF64748B),
                    height: 1.5,
                  ),
                ),
              ),
            ),

            // Total Bayar
            Expanded(
              flex: _flexTotal,
              child: Padding(
                padding: const EdgeInsets.only(right: 16),
                child: Text(
                  _formatRupiah(transaction.totalBayar),
                  style: TextStyle(
                    fontSize: 14,
                    fontWeight: FontWeight.w800,
                    color: AppColors.primary,
                  ),
                ),
              ),
            ),

            // Status
            Expanded(
              flex: _flexStatus,
              child: Padding(
                padding: const EdgeInsets.only(right: 16),
                child: Align(
                  alignment: Alignment.centerLeft,
                  child: TransactionStatusBadge(status: transaction.status),
                ),
              ),
            ),

            // Action: Tambah Cetak
            SizedBox(
              width: _colAction,
              child: Align(
                alignment: Alignment.centerRight,
                child: OutlinedButton.icon(
                  onPressed: onActionPressed,
                  icon: const Icon(Icons.print_rounded, size: 14),
                  label: const Text('Tambah Cetak'),
                  style: OutlinedButton.styleFrom(
                    foregroundColor: AppColors.primary,
                    side: BorderSide(
                      color: AppColors.primary.withValues(alpha: 0.3),
                      width: 1.5,
                    ),
                    backgroundColor: AppColors.primary.withValues(alpha: 0.04),
                    padding: const EdgeInsets.symmetric(
                      horizontal: 14,
                      vertical: 10,
                    ),
                    shape: RoundedRectangleBorder(
                      borderRadius: BorderRadius.circular(12),
                    ),
                    elevation: 0,
                    textStyle: const TextStyle(
                      fontSize: 12,
                      fontWeight: FontWeight.bold,
                    ),
                  ),
                ),
              ),
            ),
          ],
        ),
      ),
    );
  }

  String _formatWaktu(DateTime dt) {
    final day = dt.day.toString();
    final month = DateFormat('MMM').format(dt);
    final time = DateFormat('HH:mm').format(dt);
    return '$day $month, $time';
  }

  String _formatRupiah(int amount) {
    final formatted = NumberFormat('#,###', 'id_ID').format(amount);
    return 'Rp $formatted';
  }
}
