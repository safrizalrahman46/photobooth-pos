// features/history/presentation/widgets/filters/filter_button.dart

import 'package:flutter/material.dart';
import 'package:desktop_flutter/app/theme/app_colors.dart';
import '../../../domain/entities/transaction.dart';

// ─── Referensi global ──────────────────────────────────────────────────────────
// Jika project memiliki AppColors di app/theme/app_colors.dart,
// gunakan AppColors.borderColor, AppColors.textPrimary, dst.
// Contoh: import '../../../../../app/theme/app_colors.dart';

/// Tombol Filter dengan ikon, mengikuti desain History Transaksi.
///
/// Menampilkan [DropdownButton] tersembunyi di balik tampilan custom
/// agar bisa memilih filter status transaksi.
///
/// Contoh penggunaan:
/// ```dart
/// FilterButton(
///   selectedStatus: _statusFilter,
///   onChanged: controller.onStatusFilterChanged,
/// )
/// ```
class FilterButton extends StatelessWidget {
  final TransactionStatus? selectedStatus;
  final ValueChanged<TransactionStatus?> onChanged;

  const FilterButton({
    super.key,
    required this.selectedStatus,
    required this.onChanged,
  });

  @override
  Widget build(BuildContext context) {
    return Container(
      height: 40,
      decoration: BoxDecoration(
        color: Colors.white,
        border: Border.all(color: AppColors.cardBorder),
        borderRadius: BorderRadius.circular(8),
      ),
      child: PopupMenuButton<TransactionStatus?>(
        initialValue: selectedStatus,
        onSelected: onChanged,
        shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(8)),
        offset: const Offset(0, 44),
        itemBuilder: (_) => [
          const PopupMenuItem(value: null, child: Text('Semua Status')),
          ...TransactionStatus.values.map(
            (s) => PopupMenuItem(value: s, child: Text(s.label)),
          ),
        ],
        child: Padding(
          padding: const EdgeInsets.symmetric(horizontal: 14),
          child: Row(
            mainAxisSize: MainAxisSize.min,
            children: [
              // ikon filter (3 garis bertingkat)
              Icon(
                Icons.tune_rounded,
                size: 16,
                color: AppColors.textPrimary,
              ),
              const SizedBox(width: 6),
              Text(
                selectedStatus == null ? 'Filter' : selectedStatus!.label,
                style: TextStyle(
                  fontSize: 14,
                  fontWeight: FontWeight.w500,
                  color: AppColors.textPrimary,
                ),
              ),
            ],
          ),
        ),
      ),
    );
  }
}
