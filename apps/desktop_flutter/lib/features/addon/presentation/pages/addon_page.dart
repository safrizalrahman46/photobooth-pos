// lib/presentation/pages/addon_page.dart

import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:google_fonts/google_fonts.dart';
import '../providers/addon_provider.dart';
import '../theme/app_theme.dart';
import '../widgets/addon_card_widget.dart';
import '../widgets/addon_insight_panel.dart';

class AddOnPage extends ConsumerWidget {
  const AddOnPage({super.key});

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    return Scaffold(
      backgroundColor: AppColors.sidebarBg,
      body: Column(
        children: [
          // Top bar
          _TopBar(),

          // Main content
          Expanded(
            child: Row(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                // Left: stock list
                Expanded(
                  child: SingleChildScrollView(
                    padding: const EdgeInsets.fromLTRB(32, 28, 24, 32),
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        // Section header
                        _SectionHeader(),
                        const SizedBox(height: 20),

                        // Add-on list
                        _AddOnList(),
                      ],
                    ),
                  ),
                ),

                // Right: Insight panel
                Container(
                  width: 300,
                  padding: const EdgeInsets.fromLTRB(0, 28, 28, 28),
                  child: const AddonInsightPanel(),
                ),
              ],
            ),
          ),
        ],
      ),
    );
  }
}

// ─── Top Bar ──────────────────────────────────────────────────────────────────
class _TopBar extends StatelessWidget {
  @override
  Widget build(BuildContext context) {
    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 32, vertical: 18),
      decoration: BoxDecoration(
        color: AppColors.sidebarBg,
        border: Border(
          bottom: BorderSide(color: AppColors.divider, width: 1),
        ),
      ),
      child: Row(
        mainAxisAlignment: MainAxisAlignment.spaceBetween,
        children: [
          Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Text(
                'Inventory Add-ons',
                style: GoogleFonts.plusJakartaSans(
                  fontSize: 22,
                  fontWeight: FontWeight.w800,
                  color: AppColors.textPrimary,
                  letterSpacing: -0.5,
                ),
              ),
              const SizedBox(height: 3),
              Text(
                'Manage equipment, print supplies, and additional photo services.',
                style: GoogleFonts.plusJakartaSans(
                  fontSize: 12.5,
                  fontWeight: FontWeight.w400,
                  color: AppColors.textSecondary,
                ),
              ),
            ],
          ),
          _CloseShiftButton(),
        ],
      ),
    );
  }
}

class _CloseShiftButton extends StatelessWidget {
  @override
  Widget build(BuildContext context) {
    return GestureDetector(
      onTap: () {},
      child: Container(
        padding:
            const EdgeInsets.symmetric(horizontal: 22, vertical: 11),
        decoration: BoxDecoration(
          color: AppColors.darkBlue,
          borderRadius: BorderRadius.circular(24),
        ),
        child: Text(
          'Close Shift',
          style: GoogleFonts.plusJakartaSans(
            fontSize: 13,
            fontWeight: FontWeight.w600,
            color: Colors.white,
          ),
        ),
      ),
    );
  }
}

// ─── Section Header (Current Stock + Filter) ──────────────────────────────────
class _SectionHeader extends ConsumerWidget {
  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final filter = ref.watch(addonFilterProvider);

    return Row(
      mainAxisAlignment: MainAxisAlignment.spaceBetween,
      children: [
        Text(
          'Current Stock',
          style: GoogleFonts.plusJakartaSans(
            fontSize: 15,
            fontWeight: FontWeight.w700,
            color: AppColors.textPrimary,
          ),
        ),
        _FilterChip(
          currentFilter: filter,
          onChanged: (v) =>
              ref.read(addonFilterProvider.notifier).state = v,
        ),
      ],
    );
  }
}

class _FilterChip extends StatelessWidget {
  final String currentFilter;
  final ValueChanged<String> onChanged;

  const _FilterChip({
    required this.currentFilter,
    required this.onChanged,
  });

  @override
  Widget build(BuildContext context) {
    return GestureDetector(
      onTap: () {
        // Cycle through filters
        const options = ['ALL', 'STOCK', 'AVAILABLE'];
        final idx = options.indexOf(currentFilter);
        onChanged(options[(idx + 1) % options.length]);
      },
      child: Container(
        padding:
            const EdgeInsets.symmetric(horizontal: 14, vertical: 7),
        decoration: BoxDecoration(
          color: Colors.white,
          borderRadius: BorderRadius.circular(20),
          border: Border.all(color: AppColors.cardBorder),
          boxShadow: [
            BoxShadow(
              color: AppColors.shadowColor,
              blurRadius: 6,
              offset: const Offset(0, 2),
            ),
          ],
        ),
        child: Text(
          'FILTER: $currentFilter',
          style: GoogleFonts.plusJakartaSans(
            fontSize: 10.5,
            fontWeight: FontWeight.w700,
            letterSpacing: 0.6,
            color: AppColors.textSecondary,
          ),
        ),
      ),
    );
  }
}

// ─── Add-On List ──────────────────────────────────────────────────────────────
class _AddOnList extends ConsumerWidget {
  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final filteredAsync = ref.watch(filteredAddOnProvider);

    return filteredAsync.when(
      loading: () => const Center(
        child: Padding(
          padding: EdgeInsets.all(40),
          child: CircularProgressIndicator(
            color: AppColors.primaryBlue,
            strokeWidth: 2,
          ),
        ),
      ),
      error: (e, _) => Text(
        'Gagal memuat: $e',
        style: GoogleFonts.plusJakartaSans(color: AppColors.textSecondary),
      ),
      data: (list) {
        if (list.isEmpty) {
          return Center(
            child: Padding(
              padding: const EdgeInsets.all(32),
              child: Text(
                'Tidak ada add-on.',
                style: GoogleFonts.plusJakartaSans(
                  color: AppColors.textMuted,
                  fontSize: 13,
                ),
              ),
            ),
          );
        }
        return Column(
          children: list
              .map((a) => AddOnCardWidget(addOn: a))
              .toList(),
        );
      },
    );
  }
}
