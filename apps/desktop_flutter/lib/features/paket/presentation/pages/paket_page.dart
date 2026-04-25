// lib/presentation/pages/daftar_paket_page.dart

import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:google_fonts/google_fonts.dart';
import '../providers/paket_provider.dart';
import '../theme/app_theme.dart';
import '../widgets/info_banner_widget.dart';
import '../widgets/paket_card_widget.dart';
import '../widgets/search_bar_widget.dart';

class PaketPage extends ConsumerWidget {
  const PaketPage({super.key});

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final filteredAsync = ref.watch(filteredPaketProvider);

    return Scaffold(
      backgroundColor: AppColors.sidebarBg,
      body: Column(
        children: [
          // Top bar
          _TopBar(
            onSearchChanged: (q) =>
                ref.read(searchQueryProvider.notifier).state = q,
          ),

          // Content
          Expanded(
            child: SingleChildScrollView(
              padding: const EdgeInsets.fromLTRB(32, 0, 32, 32),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  const SizedBox(height: 28),

                  // Read-only mode badge
                  _ReadOnlyBadge(),

                  const SizedBox(height: 28),

                  // Package cards
                  filteredAsync.when(
                    loading: () => const Center(
                      child: Padding(
                        padding: EdgeInsets.all(40),
                        child: CircularProgressIndicator(
                          color: AppColors.primaryBlue,
                          strokeWidth: 2,
                        ),
                      ),
                    ),
                    error: (e, _) => Center(
                      child: Text(
                        'Gagal memuat data: $e',
                        style: GoogleFonts.plusJakartaSans(
                          color: AppColors.textSecondary,
                        ),
                      ),
                    ),
                    data: (list) {
                      if (list.isEmpty) {
                        return Center(
                          child: Padding(
                            padding: const EdgeInsets.all(40),
                            child: Text(
                              'Paket tidak ditemukan.',
                              style: GoogleFonts.plusJakartaSans(
                                color: AppColors.textMuted,
                                fontSize: 13,
                              ),
                            ),
                          ),
                        );
                      }

                      return SizedBox(
                        height: 380,
                        child: Row(
                          crossAxisAlignment: CrossAxisAlignment.stretch,
                          children: list.map((paket) {
                            final isHighlighted = paket.isHighlighted;
                            return Padding(
                              padding: const EdgeInsets.only(right: 16),
                              child: isHighlighted
                                  ? Transform.translate(
                                      offset: const Offset(0, -12),
                                      child: PaketCardWidget(paket: paket),
                                    )
                                  : PaketCardWidget(paket: paket),
                            );
                          }).toList(),
                        ),
                      );
                    },
                  ),

                  const SizedBox(height: 24),

                  // Info banner
                  const InfoBannerWidget(),
                ],
              ),
            ),
          ),
        ],
      ),
    );
  }
}

class _TopBar extends StatelessWidget {
  final ValueChanged<String> onSearchChanged;

  const _TopBar({required this.onSearchChanged});

  @override
  Widget build(BuildContext context) {
    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 32, vertical: 20),
      decoration: BoxDecoration(
        color: AppColors.sidebarBg,
        border: Border(bottom: BorderSide(color: AppColors.divider, width: 1)),
      ),
      child: Row(
        mainAxisAlignment: MainAxisAlignment.spaceBetween,
        children: [
          Text(
            'Daftar Paket Foto',
            style: GoogleFonts.plusJakartaSans(
              fontSize: 20,
              fontWeight: FontWeight.w700,
              color: AppColors.textPrimary,
              letterSpacing: -0.4,
            ),
          ),
          PaketSearchBar(onChanged: onSearchChanged),
        ],
      ),
    );
  }
}

class _ReadOnlyBadge extends StatelessWidget {
  @override
  Widget build(BuildContext context) {
    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 8),
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.circular(20),
        border: Border.all(color: AppColors.cardBorder, width: 1),
      ),
      child: Row(
        mainAxisSize: MainAxisSize.min,
        children: [
          const Icon(Icons.info_outline, size: 13, color: AppColors.textMuted),
          const SizedBox(width: 6),
          Text(
            'MODE PENINJAUAN (READ-ONLY)',
            style: GoogleFonts.plusJakartaSans(
              fontSize: 11,
              fontWeight: FontWeight.w600,
              letterSpacing: 0.5,
              color: AppColors.textSecondary,
            ),
          ),
        ],
      ),
    );
  }
}
