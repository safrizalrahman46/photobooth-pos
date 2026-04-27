import 'package:flutter/material.dart';
import 'package:intl/intl.dart';
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
  static const double _colAction = 140;

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
        padding: const EdgeInsets.symmetric(vertical: 24, horizontal: 20),
        child: Row(
          children: [
            // ID Transaksi
            Expanded(
              flex: _flexId,
              child: Text(
                transaction.id,
                style: const TextStyle(
                  fontSize: 15,
                  fontWeight: FontWeight.w800,
                  color: Color(0xFF111827),
                ),
              ),
            ),

            // Waktu
            Expanded(
              flex: _flexWaktu,
              child: Text(
                _formatWaktu(transaction.waktu),
                style: const TextStyle(
                  fontSize: 14,
                  color: Color(0xFF4B5563),
                  height: 1.5,
                ),
              ),
            ),

            // Nama Pelanggan
            Expanded(
              flex: _flexNama,
              child: Text(
                transaction.namaPelanggan,
                style: const TextStyle(
                  fontSize: 15,
                  fontWeight: FontWeight.w500,
                  color: Color(0xFF374151),
                ),
              ),
            ),

            // Paket & Add-ons
            Expanded(
              flex: _flexPaket,
              child: Text(
                transaction.paketDanAddOns,
                style: const TextStyle(
                  fontSize: 14,
                  color: Color(0xFF6B7280),
                  height: 1.5,
                ),
              ),
            ),

            // Total Bayar
            Expanded(
              flex: _flexTotal,
              child: Text(
                _formatRupiah(transaction.totalBayar),
                style: const TextStyle(
                  fontSize: 15,
                  fontWeight: FontWeight.w800,
                  color: Color(0xFF111827),
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

            // Action: Cetak Ulang
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
                  icon: const Icon(Icons.print_rounded, size: 16),
                  label: const Text(
                    'Cetak Ulang',
                    style: TextStyle(fontSize: 13, fontWeight: FontWeight.w600),
                  ),
                  style: TextButton.styleFrom(
                    foregroundColor: const Color(0xFF6366F1), // indigo-500
                    padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 8),
                    shape: RoundedRectangleBorder(
                      borderRadius: BorderRadius.circular(8),
                      side: const BorderSide(color: Color(0xFFE0E7FF)), // indigo-100
                    ),
                    backgroundColor: const Color(0xFFF5F7FF),
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
