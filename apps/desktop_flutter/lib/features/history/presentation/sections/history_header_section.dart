// features/history/presentation/sections/history_header_section.dart

import 'package:flutter/material.dart';
import '../widgets/filters/filter_button.dart';
import '../widgets/filters/export_button.dart';
import '../../domain/entities/transaction.dart';

// ─── Referensi global ──────────────────────────────────────────────────────────
// Gunakan SearchField dari shared/widgets/inputs/search_field.dart
// sebagai pengganti TextField di bawah agar konsisten di seluruh aplikasi.
// Contoh: import '../../../../shared/widgets/inputs/search_field.dart';
//
// Gunakan AppTextStyles dari app/theme/app_text_styles.dart untuk teks judul.

/// Section header halaman History: search bar + tombol Filter & Export.
///
/// Contoh penggunaan:
/// ```dart
/// HistoryHeaderSection(
///   searchQuery: controller.searchQuery,
///   onSearchChanged: controller.onSearchChanged,
///   selectedStatus: controller.statusFilter,
///   onStatusFilterChanged: controller.onStatusFilterChanged,
///   onExport: controller.onExport,
/// )
/// ```
class HistoryHeaderSection extends StatelessWidget {
  final String searchQuery;
  final ValueChanged<String> onSearchChanged;
  final TransactionStatus? selectedStatus;
  final ValueChanged<TransactionStatus?> onStatusFilterChanged;
  final VoidCallback onExport;

  const HistoryHeaderSection({
    super.key,
    required this.searchQuery,
    required this.onSearchChanged,
    required this.selectedStatus,
    required this.onStatusFilterChanged,
    required this.onExport,
  });

  @override
  Widget build(BuildContext context) {
    return Row(
      children: [
        // ── Search field ──────────────────────────────────────────────────
        // Ganti dengan SearchField dari shared/widgets/inputs/search_field.dart
        Expanded(
          child: SizedBox(
            height: 42,
            child: TextField(
              controller: TextEditingController(text: searchQuery)
                ..selection = TextSelection.collapsed(
                  offset: searchQuery.length,
                ),
              onChanged: onSearchChanged,
              style: const TextStyle(fontSize: 14),
              decoration: InputDecoration(
                hintText: 'Cari No. Transaksi atau Nama...',
                hintStyle: const TextStyle(
                  color: Color(0xFF9CA3AF),
                  fontSize: 14,
                ),
                prefixIcon: const Icon(
                  Icons.search_rounded,
                  size: 18,
                  color: Color(0xFF9CA3AF),
                ),
                filled: true,
                fillColor: Colors.white,
                contentPadding: const EdgeInsets.symmetric(vertical: 0),
                enabledBorder: OutlineInputBorder(
                  borderRadius: BorderRadius.circular(8),
                  borderSide: const BorderSide(color: Color(0xFFE5E7EB)),
                ),
                focusedBorder: OutlineInputBorder(
                  borderRadius: BorderRadius.circular(8),
                  borderSide: const BorderSide(
                    color: Color(0xFF3B82F6), // blue-500
                    width: 1.5,
                  ),
                ),
              ),
            ),
          ),
        ),

        const SizedBox(width: 12),

        // ── Filter button ─────────────────────────────────────────────────
        FilterButton(
          selectedStatus: selectedStatus,
          onChanged: onStatusFilterChanged,
        ),

        const SizedBox(width: 8),

        // ── Export button ─────────────────────────────────────────────────
        ExportButton(onPressed: onExport),
      ],
    );
  }
}
