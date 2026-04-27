// features/history/presentation/widgets/common/transaction_status_badge.dart

import 'package:flutter/material.dart';
import '../../../domain/entities/transaction.dart';

// ─── Referensi global (gunakan dari shared/widgets/common/status_badge.dart) ──
// Widget ini adalah versi spesifik untuk TransactionStatus.
// Jika project sudah memiliki StatusBadge di shared/widgets/common/status_badge.dart,
// pertimbangkan untuk meng-extend atau menggunakan StatusBadge tersebut
// agar tidak ada duplikasi logika warna/label.

/// Badge status transaksi (Lunas / Pending / Batal)
///
/// Contoh penggunaan:
/// ```dart
/// TransactionStatusBadge(status: TransactionStatus.lunas)
/// ```
class TransactionStatusBadge extends StatelessWidget {
  final TransactionStatus status;

  const TransactionStatusBadge({super.key, required this.status});

  @override
  Widget build(BuildContext context) {
    final config = _badgeConfig(status);

    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 5),
      decoration: BoxDecoration(
        color: config.backgroundColor,
        borderRadius: BorderRadius.circular(20),
      ),
      child: Text(
        status.label,
        style: TextStyle(
          fontSize: 12,
          fontWeight: FontWeight.w500,
          color: config.textColor,
        ),
      ),
    );
  }

  _BadgeConfig _badgeConfig(TransactionStatus status) {
    switch (status) {
      case TransactionStatus.lunas:
        return _BadgeConfig(
          backgroundColor: const Color(0xFFDCFCE7), // green-100
          textColor: const Color(0xFF16A34A), // green-600
        );
      case TransactionStatus.batal:
        return _BadgeConfig(
          backgroundColor: const Color(0xFFFEE2E2), // red-100
          textColor: const Color(0xFFDC2626), // red-600
        );
    }
  }
}

class _BadgeConfig {
  final Color backgroundColor;
  final Color textColor;
  const _BadgeConfig({required this.backgroundColor, required this.textColor});
}
