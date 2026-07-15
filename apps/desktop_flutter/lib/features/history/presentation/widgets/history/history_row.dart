import 'package:flutter/material.dart';
import 'package:intl/intl.dart';
import 'package:desktop_flutter/app/theme/app_colors.dart';
import 'package:desktop_flutter/core/session/api_session.dart';
import '../../../domain/entities/transaction.dart';
import '../common/transaction_status_badge.dart';

class HistoryRow extends StatelessWidget {
  final Transaction transaction;
  final VoidCallback? onLunasi;
  final VoidCallback? onReprint;
  final VoidCallback? onExtraPrint;
  final VoidCallback? onUbahMetode;

  static const int _flexId = 2;
  static const int _flexWaktu = 3;
  static const int _flexNama = 3;
  static const int _flexPaket = 3;
  static const int _flexTotal = 2;
  static const int _flexStatus = 3;


  const HistoryRow({
    super.key,
    required this.transaction,
    this.onLunasi,
    this.onReprint,
    this.onExtraPrint,
    this.onUbahMetode,
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
            Expanded(flex: _flexId, child: Padding(
              padding: const EdgeInsets.only(right: 16),
              child: Text(transaction.id, overflow: TextOverflow.ellipsis, style: const TextStyle(fontSize: 14, fontWeight: FontWeight.bold, color: Color(0xFF1E293B))),
            )),
            Expanded(flex: _flexWaktu, child: Padding(
              padding: const EdgeInsets.only(right: 16),
              child: Text(_formatWaktu(transaction.waktu), overflow: TextOverflow.ellipsis, style: const TextStyle(fontSize: 13, color: Color(0xFF64748B), height: 1.5)),
            )),
            Expanded(flex: _flexNama, child: Padding(
              padding: const EdgeInsets.only(right: 16),
              child: Text(transaction.namaPelanggan, overflow: TextOverflow.ellipsis, style: const TextStyle(fontSize: 14, fontWeight: FontWeight.w600, color: Color(0xFF1E293B))),
            )),
            Expanded(flex: _flexPaket, child: Padding(
              padding: const EdgeInsets.only(right: 16),
              child: Text(transaction.paketDanAddOns, overflow: TextOverflow.ellipsis, style: const TextStyle(fontSize: 13, color: Color(0xFF64748B), height: 1.5)),
            )),
            Expanded(flex: _flexTotal, child: Padding(
              padding: const EdgeInsets.only(right: 16),
              child: Text(_formatRupiah(transaction.totalAmount), overflow: TextOverflow.ellipsis, style: const TextStyle(fontSize: 14, fontWeight: FontWeight.w800, color: AppColors.textPrimary)),
            )),
            Expanded(flex: _flexStatus, child: Padding(
              padding: const EdgeInsets.only(right: 16),
              child: Align(alignment: Alignment.centerLeft, child: TransactionStatusBadge(status: transaction.status)),
            )),
            Expanded(
              flex: 3,
              child: Align(
                alignment: Alignment.centerRight,
                child: _buildActionButtons(),
              ),
            ),
          ],
        ),
      ),
    );
  }

  Widget _buildActionButtons() {
    final isOwner = ApiSession.current?.user.hasRole('owner') ?? false;

    return switch (transaction.status) {
      TransactionStatus.dp => _actionButton(label: 'Lunasi', icon: Icons.check_circle_rounded, onPressed: onLunasi, color: const Color(0xFFEA580C)),
      TransactionStatus.lunas => Wrap(
          spacing: 8,
          runSpacing: 6,
          alignment: WrapAlignment.end,
          children: [
            if (isOwner && onUbahMetode != null)
              _actionButton(label: 'Ubah Metode', icon: Icons.swap_horiz_rounded, onPressed: onUbahMetode, color: const Color(0xFF7C3AED)),
            _actionButton(label: 'Cetak Ulang', icon: Icons.replay_rounded, onPressed: onReprint, color: AppColors.primary),
            _actionButton(label: 'Tambah Cetak', icon: Icons.print_rounded, onPressed: onExtraPrint, color: AppColors.primary),
          ],
        ),
      _ => const SizedBox.shrink(),
    };
  }

  Widget _actionButton({
    required String label,
    required IconData icon,
    required VoidCallback? onPressed,
    required Color color,
  }) {
    return OutlinedButton.icon(
      onPressed: onPressed,
      icon: Icon(icon, size: 14),
      label: Text(label),
      style: OutlinedButton.styleFrom(
        foregroundColor: color,
        side: BorderSide(color: color.withValues(alpha: 0.3), width: 1.5),
        backgroundColor: color.withValues(alpha: 0.04),
        padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 10),
        shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
        elevation: 0,
        textStyle: const TextStyle(fontSize: 12, fontWeight: FontWeight.bold),
      ),
    );
  }

  String _formatWaktu(DateTime dt) {
    final day = dt.day.toString();
    final month = DateFormat('MMM').format(dt);
    final time = DateFormat('HH:mm').format(dt);
    return '$day $month, $time';
  }

  String _formatRupiah(double amount) {
    final formatted = NumberFormat('#,###', 'id_ID').format(amount);
    return 'Rp $formatted';
  }
}