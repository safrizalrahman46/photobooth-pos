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
    return SizedBox(
      height: 40,
      child: OutlinedButton.icon(
        onPressed: onPressed,
        icon: Icon(
          Icons.download_rounded,
          size: 16,
          color: AppColors.textPrimary,
        ),
        label: Text(
          'Export',
          style: TextStyle(
            fontSize: 14,
            fontWeight: FontWeight.w500,
            color: AppColors.textPrimary,
          ),
        ),
        style: OutlinedButton.styleFrom(
          backgroundColor: Colors.white,
          side: BorderSide(color: AppColors.cardBorder),
          shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(8)),
          padding: const EdgeInsets.symmetric(horizontal: 14),
        ),
      ),
    );
  }
}
