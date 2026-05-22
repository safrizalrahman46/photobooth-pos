// lib/presentation/pages/addon_page.dart

import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:google_fonts/google_fonts.dart';
import '../../domain/entities/add_on.dart';
import '../providers/addon_provider.dart';
import '../theme/app_theme.dart';

class AddOnPage extends ConsumerWidget {
  const AddOnPage({super.key});

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    return Scaffold(
      backgroundColor: AppColors.sidebarBg,
      body: SingleChildScrollView(
        padding: const EdgeInsets.fromLTRB(50, 64, 50, 50),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            _PageHeader(),
            const SizedBox(height: 32),
            _StockSummaryTable(),
          ],
        ),
      ),
    );
  }
}

class _PageHeader extends StatelessWidget {
  @override
  Widget build(BuildContext context) {
    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Text(
          'Inventory Add-ons',
          style: GoogleFonts.plusJakartaSans(
            fontSize: 28,
            fontWeight: FontWeight.w800,
            color: AppColors.textPrimary,
            letterSpacing: -0.7,
          ),
        ),
        const SizedBox(height: 6),
        Text(
          'Monitoring stok perlengkapan dan layanan tambahan secara real-time.',
          style: GoogleFonts.plusJakartaSans(
            fontSize: 14,
            fontWeight: FontWeight.w500,
            color: AppColors.textSecondary,
          ),
        ),
      ],
    );
  }
}

class _StockSummaryTable extends ConsumerWidget {
  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final addonsAsync = ref.watch(filteredAddOnProvider);

    return Container(
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.circular(24),
        border: Border.all(color: AppColors.cardBorder, width: 1.5),
        boxShadow: [
          BoxShadow(
            color: AppColors.shadowColor.withValues(alpha: 0.08),
            blurRadius: 20,
            offset: const Offset(0, 10),
          ),
        ],
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        mainAxisSize: MainAxisSize.min,
        children: [
          Padding(
            padding: const EdgeInsets.all(24),
            child: Row(
              children: [
                const Icon(
                  Icons.inventory_2_rounded,
                  color: AppColors.primaryBlue,
                  size: 22,
                ),
                const SizedBox(width: 12),
                Text(
                  'RINGKASAN STOK',
                  style: GoogleFonts.plusJakartaSans(
                    fontSize: 13,
                    fontWeight: FontWeight.w800,
                    letterSpacing: 1.2,
                    color: AppColors.textPrimary,
                  ),
                ),
              ],
            ),
          ),
          const Divider(height: 1, thickness: 1, color: Color(0xFFF3F4F6)),

          Padding(
            padding: const EdgeInsets.fromLTRB(24, 20, 24, 10),
            child: addonsAsync.when(
              data: (list) {
                return Column(
                  children: list.map((item) {
                    final isLow =
                        item.statusType == AddOnStatusType.stockLevel &&
                        (item.sisaStok ?? 0) < 15;
                    return Padding(
                      padding: const EdgeInsets.only(bottom: 18),
                      child: Row(
                        mainAxisAlignment: MainAxisAlignment.spaceBetween,
                        children: [
                          Expanded(
                            child: Text(
                              item.nama,
                              style: GoogleFonts.plusJakartaSans(
                                fontSize: 13,
                                fontWeight: FontWeight.w600,
                                color: AppColors.textSecondary,
                              ),
                              overflow: TextOverflow.ellipsis,
                            ),
                          ),
                          Container(
                            padding: const EdgeInsets.symmetric(
                              horizontal: 12,
                              vertical: 6,
                            ),
                            decoration: BoxDecoration(
                              color: isLow
                                  ? const Color(0xFFFEF2F2)
                                  : const Color(0xFFF9FAFB),
                              borderRadius: BorderRadius.circular(8),
                              border: Border.all(
                                color: isLow
                                    ? const Color(0xFFFEE2E2)
                                    : Colors.transparent,
                              ),
                            ),
                            child: Text(
                              item.statusType == AddOnStatusType.stockLevel
                                  ? '${item.sisaStok}'
                                  : 'Ready',
                              style: GoogleFonts.plusJakartaSans(
                                fontSize: 13,
                                fontWeight: FontWeight.w800,
                                color: isLow
                                    ? const Color(0xFFEF4444)
                                    : AppColors.textPrimary,
                              ),
                            ),
                          ),
                        ],
                      ),
                    );
                  }).toList(),
                );
              },
              loading: () => const Center(
                child: Padding(
                  padding: EdgeInsets.all(20.0),
                  child: CircularProgressIndicator(strokeWidth: 2),
                ),
              ),
              error: (_, __) => const Text('Error loading stock'),
            ),
          ),
        ],
      ),
    );
  }
}
