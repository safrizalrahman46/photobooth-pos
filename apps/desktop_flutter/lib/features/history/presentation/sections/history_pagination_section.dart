// features/history/presentation/sections/history_pagination_section.dart

import 'package:flutter/material.dart';
import 'package:desktop_flutter/app/theme/app_colors.dart';

// ─── Referensi global ──────────────────────────────────────────────────────────
// Gunakan AppColors dari app/theme/app_colors.dart untuk warna primary.
// Contoh: import '../../../../app/theme/app_colors.dart';

/// Section pagination di bagian bawah tabel History.
///
/// Menampilkan:
/// - Label "Menampilkan X-Y dari Z transaksi"
/// - Tombol Prev / Next
/// - Nomor halaman (max tampil 3 di tengah + ellipsis + halaman terakhir)
///
/// Contoh penggunaan:
/// ```dart
/// HistoryPaginationSection(
///   currentPage: controller.currentPage,
///   totalPages: controller.totalPages,
///   paginationLabel: controller.paginationLabel,
///   onPageChanged: controller.goToPage,
///   onPrev: controller.prevPage,
///   onNext: controller.nextPage,
/// )
/// ```
class HistoryPaginationSection extends StatelessWidget {
  final int currentPage;
  final int totalPages;
  final String paginationLabel;
  final void Function(int page) onPageChanged;
  final VoidCallback onPrev;
  final VoidCallback onNext;

  const HistoryPaginationSection({
    super.key,
    required this.currentPage,
    required this.totalPages,
    required this.paginationLabel,
    required this.onPageChanged,
    required this.onPrev,
    required this.onNext,
  });

  @override
  Widget build(BuildContext context) {
    return Row(
      mainAxisAlignment: MainAxisAlignment.spaceBetween,
      children: [
        // Label info
        Text(
          paginationLabel,
          style: TextStyle(
            fontSize: 13,
            color: AppColors.textSecondary,
          ),
        ),

        // Kontrol navigasi
        Row(
          children: [
            _NavButton(
              label: 'Prev',
              onPressed: currentPage > 1 ? onPrev : null,
            ),
            const SizedBox(width: 4),
            ..._buildPageNumbers(),
            const SizedBox(width: 4),
            _NavButton(
              label: 'Next',
              onPressed: currentPage < totalPages ? onNext : null,
            ),
          ],
        ),
      ],
    );
  }

  /// Membangun list widget nomor halaman dengan ellipsis bila perlu.
  /// Pola: 1 2 3 … 25  (halaman awal) atau  1 … 12 13 14 … 25
  List<Widget> _buildPageNumbers() {
    final pages = <Widget>[];

    if (totalPages <= 5) {
      // Tampilkan semua halaman
      for (int i = 1; i <= totalPages; i++) {
        pages.add(
          _PageButton(
            page: i,
            isActive: i == currentPage,
            onTap: () => onPageChanged(i),
          ),
        );
      }
    } else {
      // Selalu tampilkan halaman 1
      pages.add(
        _PageButton(
          page: 1,
          isActive: currentPage == 1,
          onTap: () => onPageChanged(1),
        ),
      );

      if (currentPage > 3) {
        pages.add(const _Ellipsis());
      }

      // Halaman di sekitar currentPage
      final start = (currentPage - 1).clamp(2, totalPages - 1);
      final end = (currentPage + 1).clamp(2, totalPages - 1);
      for (int i = start; i <= end; i++) {
        pages.add(
          _PageButton(
            page: i,
            isActive: i == currentPage,
            onTap: () => onPageChanged(i),
          ),
        );
      }

      if (currentPage < totalPages - 2) {
        pages.add(const _Ellipsis());
      }

      // Selalu tampilkan halaman terakhir
      pages.add(
        _PageButton(
          page: totalPages,
          isActive: currentPage == totalPages,
          onTap: () => onPageChanged(totalPages),
        ),
      );
    }

    return pages;
  }
}

// ─── Sub-widgets ───────────────────────────────────────────────────────────────

class _NavButton extends StatelessWidget {
  final String label;
  final VoidCallback? onPressed;

  const _NavButton({required this.label, this.onPressed});

  @override
  Widget build(BuildContext context) {
    final isDisabled = onPressed == null;
    return GestureDetector(
      onTap: onPressed,
      child: Container(
        padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 7),
        decoration: BoxDecoration(
          color: Colors.white,
          border: Border.all(color: AppColors.cardBorder),
          borderRadius: BorderRadius.circular(8),
        ),
        child: Text(
          label,
          style: TextStyle(
            fontSize: 13,
            fontWeight: FontWeight.w500,
            color: isDisabled
                ? AppColors.textMuted
                : AppColors.textPrimary,
          ),
        ),
      ),
    );
  }
}

class _PageButton extends StatelessWidget {
  final int page;
  final bool isActive;
  final VoidCallback onTap;

  const _PageButton({
    required this.page,
    required this.isActive,
    required this.onTap,
  });

  @override
  Widget build(BuildContext context) {
    return GestureDetector(
      onTap: onTap,
      child: Container(
        width: 36,
        height: 36,
        margin: const EdgeInsets.symmetric(horizontal: 2),
        decoration: BoxDecoration(
          // Warna aktif biru sesuai desain
          // Ganti Color(0xFF3B82F6) dengan AppColors.primary jika tersedia
          color: isActive ? AppColors.primary : Colors.transparent,
          borderRadius: BorderRadius.circular(8),
          border: isActive ? null : Border.all(color: AppColors.cardBorder),
        ),
        alignment: Alignment.center,
        child: Text(
          '$page',
          style: TextStyle(
            fontSize: 13,
            fontWeight: isActive ? FontWeight.w700 : FontWeight.w400,
            color: isActive ? Colors.white : AppColors.textPrimary,
          ),
        ),
      ),
    );
  }
}

class _Ellipsis extends StatelessWidget {
  const _Ellipsis();

  @override
  Widget build(BuildContext context) {
    return Padding(
      padding: const EdgeInsets.symmetric(horizontal: 4),
      child: Text('…', style: TextStyle(color: AppColors.textMuted)),
    );
  }
}
