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
      body: SingleChildScrollView(
        padding: const EdgeInsets.fromLTRB(50, 60, 50, 50), // Padding lega tapi nge-fit
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            // Header Section (Title & Search)
            _PageHeader(
              onSearchChanged: (q) =>
                  ref.read(searchQueryProvider.notifier).state = q,
            ),

            const SizedBox(height: 28),

            // Read-only mode badge
            _ReadOnlyBadge(),

            const SizedBox(height: 32),

            // Package cards (Responsive & Equal Height - FULL WIDTH)
            filteredAsync.when(
              loading: () => const Center(
                child: Padding(
                  padding: EdgeInsets.all(60),
                  child: CircularProgressIndicator(
                    color: AppColors.primaryBlue,
                    strokeWidth: 3,
                  ),
                ),
              ),
              error: (e, _) => Center(
                child: Text('Gagal memuat data: $e'),
              ),
              data: (list) {
                if (list.isEmpty) {
                  return const Center(
                    child: Padding(
                      padding: EdgeInsets.all(40),
                      child: Text('Paket tidak ditemukan.'),
                    ),
                  );
                }

                // Menggunakan Row dengan IntrinsicHeight agar semua kartu sejajar tinggi
                return IntrinsicHeight(
                  child: Row(
                    crossAxisAlignment: CrossAxisAlignment.stretch,
                    children: list.map((paket) {
                      return Expanded(
                        child: Padding(
                          padding: const EdgeInsets.symmetric(horizontal: 10),
                          child: PaketCardWidget(paket: paket),
                        ),
                      );
                    }).toList(),
                  ),
                );
              },
            ),

            const SizedBox(height: 40),

            // Info banner (Sekarang melebar penuh)
            const SizedBox(
              width: double.infinity,
              child: InfoBannerWidget(),
            ),
          ],
        ),
      ),
    );
  }
}

class _PageHeader extends StatelessWidget {
  final ValueChanged<String> onSearchChanged;

  const _PageHeader({required this.onSearchChanged});

  @override
  Widget build(BuildContext context) {
    return Row(
      mainAxisAlignment: MainAxisAlignment.spaceBetween,
      children: [
        Text(
          'Daftar Paket Foto',
          style: GoogleFonts.plusJakartaSans(
            fontSize: 24,
            fontWeight: FontWeight.w800,
            color: AppColors.textPrimary,
            letterSpacing: -0.5,
          ),
        ),
        SizedBox(
          width: 300,
          child: PaketSearchBar(onChanged: onSearchChanged),
        ),
      ],
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
          const Icon(Icons.info_outline, size: 14, color: AppColors.textMuted),
          const SizedBox(width: 8),
          Text(
            'MODE PENINJAUAN (READ-ONLY)',
            style: GoogleFonts.plusJakartaSans(
              fontSize: 11,
              fontWeight: FontWeight.w700,
              letterSpacing: 0.8,
              color: AppColors.textSecondary,
            ),
          ),
        ],
      ),
    );
  }
}
