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
  static const int _flexWaktu = 2;
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
      decoration: BoxDecoration(
        border: Border(
          bottom: BorderSide(color: AppColors.divider),
        ),
      ),
      child: Padding(
        padding: const EdgeInsets.symmetric(vertical: 24, horizontal: 20),
        child: Row(
          children: [
            // ID Transaksi
            Expanded(
              flex: _flexId,
              child: Text(
                transaction.id,
                style: TextStyle(
                  fontSize: 15,
                  fontWeight: FontWeight.w800,
                  color: AppColors.textPrimary,
                ),
              ),
            ),

            // Waktu
            Expanded(
              flex: _flexWaktu,
              child: Text(
                _formatWaktu(transaction.waktu),
                style: TextStyle(
                  fontSize: 14,
                  color: AppColors.textSecondary,
                  height: 1.5,
                ),
              ),
            ),

            // Nama Pelanggan
            Expanded(
              flex: _flexNama,
              child: Text(
                transaction.namaPelanggan,
                style: TextStyle(
                  fontSize: 15,
                  fontWeight: FontWeight.w500,
                  color: AppColors.textPrimary,
                ),
              ),
            ),

            // Paket & Add-ons
            Expanded(
              flex: _flexPaket,
              child: Text(
                transaction.paketDanAddOns,
                style: TextStyle(
                  fontSize: 14,
                  color: AppColors.textSecondary,
                  height: 1.5,
                ),
              ),
            ),

            // Total Bayar
            Expanded(
              flex: _flexTotal,
              child: Text(
                _formatRupiah(transaction.totalBayar),
                style: TextStyle(
                  fontSize: 15,
                  fontWeight: FontWeight.w800,
                  color: AppColors.textPrimary,
                ),
              ),
            ),

            // Status
            Expanded(
              flex: _flexStatus,
              child: Align(
                alignment: Alignment.centerLeft,
                child: TransactionStatusBadge(status: transaction.status),
              ),
            ),

            // Action: Tambah Cetak
            SizedBox(
              width: _colAction,
              child: Align(
                alignment: Alignment.centerRight,
                child: TextButton.icon(
                  onPressed: () {
                    // Di production, panggil _controller.onReprint(transaction)
                    // Untuk sekarang kita asumsikan controller dikirim atau via callback
                    onActionPressed();
                  },
                  icon: const Icon(Icons.add_circle_outline_rounded, size: 16),
                  label: const Text(
                    'Tambah Cetak',
                    style: TextStyle(fontSize: 13, fontWeight: FontWeight.w600),
                  ),
                  style: TextButton.styleFrom(
                    foregroundColor: AppColors.primary,
                    padding: const EdgeInsets.symmetric(
                      horizontal: 12,
                      vertical: 8,
                    ),
                    shape: RoundedRectangleBorder(
                      borderRadius: BorderRadius.circular(8),
                      side: BorderSide(
                        color: AppColors.primaryLight,
                      ),
                    ),
                    backgroundColor: AppColors.cardBg,
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
