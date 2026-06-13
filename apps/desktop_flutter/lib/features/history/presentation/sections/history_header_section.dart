// features/history/presentation/sections/history_header_section.dart

import 'package:flutter/material.dart';
import 'package:desktop_flutter/app/theme/app_colors.dart';
import '../widgets/filters/filter_button.dart';
import '../widgets/filters/export_button.dart';
import '../../domain/entities/transaction.dart';

/// Section header halaman History: search bar + tombol Filter & Export.
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
        Expanded(
          child: Container(
            decoration: BoxDecoration(
              color: Colors.white,
              borderRadius: BorderRadius.circular(18),
              boxShadow: [
                BoxShadow(
                  color: Colors.black.withValues(alpha: 0.02),
                  blurRadius: 10,
                  offset: const Offset(0, 4),
                ),
              ],
            ),
            child: TextField(
              controller: TextEditingController(text: searchQuery)
                ..selection = TextSelection.collapsed(
                  offset: searchQuery.length,
                ),
              onChanged: onSearchChanged,
              style: const TextStyle(fontWeight: FontWeight.w600, fontSize: 14),
              decoration: InputDecoration(
                hintText: 'Cari No. Transaksi atau Nama...',
                hintStyle: const TextStyle(color: Colors.grey, fontWeight: FontWeight.normal),
                prefixIcon: const Icon(Icons.search_rounded, color: Color(0xFF64748B)),
                filled: true,
                fillColor: Colors.transparent,
                contentPadding: const EdgeInsets.symmetric(horizontal: 20, vertical: 18),
                border: OutlineInputBorder(
                  borderRadius: BorderRadius.circular(18),
                  borderSide: const BorderSide(color: Color(0xFFE2E8F0)),
                ),
                enabledBorder: OutlineInputBorder(
                  borderRadius: BorderRadius.circular(18),
                  borderSide: const BorderSide(color: Color(0xFFE2E8F0)),
                ),
                focusedBorder: OutlineInputBorder(
                  borderRadius: BorderRadius.circular(18),
                  borderSide: const BorderSide(color: AppColors.primary, width: 2),
                ),
              ),
            ),
          ),
        ),

        const SizedBox(width: 14),

        // ── Filter button ─────────────────────────────────────────────────
        FilterButton(
          selectedStatus: selectedStatus,
          onChanged: onStatusFilterChanged,
        ),

        const SizedBox(width: 10),

        // ── Export button ─────────────────────────────────────────────────
        ExportButton(onPressed: onExport),
      ],
    );
  }
}
