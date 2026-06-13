// features/history/presentation/widgets/filters/export_button.dart

import 'package:flutter/material.dart';
import 'package:desktop_flutter/app/theme/app_colors.dart';

// ─── Referensi global ──────────────────────────────────────────────────────────
// Jika project memiliki AppColors di app/theme/app_colors.dart,
// gunakan AppColors.borderColor dst.
// Contoh: import '../../../../../app/theme/app_colors.dart';

/// Tombol Export dengan ikon download, mengikuti desain History Transaksi.
///
/// Contoh penggunaan:
/// ```dart
/// ExportButton(onPressed: controller.onExport)
/// ```
class ExportButton extends StatelessWidget {
  final VoidCallback onPressed;

  const ExportButton({super.key, required this.onPressed});

  @override
  Widget build(BuildContext context) {
    return Container(
      height: 56,
      decoration: BoxDecoration(
        boxShadow: [
          BoxShadow(
            color: Colors.black.withValues(alpha: 0.02),
            blurRadius: 10,
            offset: const Offset(0, 4),
          ),
        ],
      ),
      child: OutlinedButton.icon(
        onPressed: onPressed,
        icon: Icon(
          Icons.download_rounded,
          size: 20,
          color: AppColors.textPrimary,
        ),
        label: Text(
          'Export Excel',
          style: TextStyle(
            fontSize: 14,
            fontWeight: FontWeight.w600,
            color: AppColors.textPrimary,
          ),
        ),
        style: OutlinedButton.styleFrom(
          backgroundColor: Colors.white,
          side: const BorderSide(color: Color(0xFFE2E8F0)),
          shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(18)),
          padding: const EdgeInsets.symmetric(horizontal: 24),
        ),
      ),
    );
  }
}
