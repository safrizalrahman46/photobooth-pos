// features/history/presentation/widgets/history/history_empty.dart

import 'package:flutter/material.dart';
import 'package:desktop_flutter/app/theme/app_colors.dart';

/// Widget empty state ketika tidak ada transaksi yang sesuai filter/pencarian.
///
/// Contoh penggunaan:
/// ```dart
/// if (transactions.isEmpty) const HistoryEmpty()
/// ```
class HistoryEmpty extends StatelessWidget {
  const HistoryEmpty({super.key});

  @override
  Widget build(BuildContext context) {
    return Center(
      child: Padding(
        padding: const EdgeInsets.symmetric(vertical: 64),
        child: Column(
          mainAxisSize: MainAxisSize.min,
          children: [
            Icon(
              Icons.receipt_long_outlined,
              size: 48,
              color: AppColors.textMuted,
            ),
            SizedBox(height: 16),
            Text(
              'Tidak ada transaksi ditemukan',
              style: TextStyle(
                fontSize: 14,
                fontWeight: FontWeight.w500,
                color: AppColors.textSecondary,
              ),
            ),
            SizedBox(height: 4),
            Text(
              'Coba ubah kata kunci atau filter pencarian.',
              style: TextStyle(
                fontSize: 13,
                color: AppColors.textMuted,
              ),
            ),
          ],
        ),
      ),
    );
  }
}
