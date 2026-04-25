// features/history/presentation/widgets/filters/export_button.dart

import 'package:flutter/material.dart';

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
        icon: const Icon(
          Icons.download_rounded,
          size: 16,
          color: Color(0xFF374151),
        ),
        label: const Text(
          'Export',
          style: TextStyle(
            fontSize: 14,
            fontWeight: FontWeight.w500,
            color: Color(0xFF374151),
          ),
        ),
        style: OutlinedButton.styleFrom(
          backgroundColor: Colors.white,
          side: const BorderSide(color: Color(0xFFE5E7EB)),
          shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(8)),
          padding: const EdgeInsets.symmetric(horizontal: 14),
        ),
      ),
    );
  }
}
