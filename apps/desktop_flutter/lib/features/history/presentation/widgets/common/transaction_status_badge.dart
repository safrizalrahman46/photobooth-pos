// features/history/presentation/widgets/common/transaction_status_badge.dart

import 'package:flutter/material.dart';
import 'package:desktop_flutter/app/theme/app_colors.dart';
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
      padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 6),
      decoration: BoxDecoration(
        color: config.backgroundColor,
        borderRadius: BorderRadius.circular(99),
        border: Border.all(color: config.textColor.withValues(alpha: 0.15)),
      ),
      child: Row(
        mainAxisSize: MainAxisSize.min,
        children: [
          Container(
            width: 6,
            height: 6,
            decoration: BoxDecoration(
              color: config.textColor,
              shape: BoxShape.circle,
            ),
          ),
          const SizedBox(width: 8),
          Flexible(
            child: Text(
              status.label,
              overflow: TextOverflow.ellipsis,
              style: TextStyle(
                fontSize: 12,
                fontWeight: FontWeight.w700,
                color: config.textColor,
              ),
            ),
          ),
        ],
      ),
    );
  }

  _BadgeConfig _badgeConfig(TransactionStatus status) {
    switch (status) {
      case TransactionStatus.lunas:
        return _BadgeConfig(
          backgroundColor: AppColors.success.withValues(alpha: 0.1),
          textColor: AppColors.success,
        );
      case TransactionStatus.pending:
        return _BadgeConfig(
          backgroundColor: AppColors.warning.withValues(alpha: 0.1),
          textColor: AppColors.warning,
        );
      case TransactionStatus.dp:
        return _BadgeConfig(
          backgroundColor: const Color(0xFFEA580C).withValues(alpha: 0.1),
          textColor: const Color(0xFFEA580C),
        );
      case TransactionStatus.batal:
        return _BadgeConfig(
          backgroundColor: AppColors.error.withValues(alpha: 0.1),
          textColor: AppColors.error,
        );
    }
  }
}

class _BadgeConfig {
  final Color backgroundColor;
  final Color textColor;
  const _BadgeConfig({required this.backgroundColor, required this.textColor});
}
