// lib/presentation/widgets/addon_card_widget.dart

import 'package:flutter/material.dart';
import 'package:google_fonts/google_fonts.dart';
import '../../domain/entities/add_on.dart';
import '../theme/app_theme.dart';

class AddOnCardWidget extends StatelessWidget {
  final AddOn addOn;

  const AddOnCardWidget({super.key, required this.addOn});

  IconData _resolveIcon() {
    switch (addOn.iconType) {
      case 'frame':
        return Icons.crop_free_rounded;
      case 'person':
        return Icons.person_add_alt_1_outlined;
      case 'costume':
        return Icons.checkroom_outlined;
      case 'print':
      default:
        return Icons.photo_outlined;
    }
  }

  @override
  Widget build(BuildContext context) {
    return Container(
      margin: const EdgeInsets.only(bottom: 12),
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.circular(14),
        border: Border.all(color: AppColors.cardBorder, width: 1),
        boxShadow: [
          BoxShadow(
            color: AppColors.shadowColor,
            blurRadius: 8,
            offset: const Offset(0, 2),
          ),
        ],
      ),
      child: ClipRRect(
        borderRadius: BorderRadius.circular(14),
        child: IntrinsicHeight(
          child: Row(
            children: [
              // Left accent bar
              Container(
                width: 4,
                decoration: BoxDecoration(
                  color: addOn.isHighlightedLeft
                      ? AppColors.primaryBlue
                      : Colors.transparent,
                ),
              ),

              // Content
              Expanded(
                child: Padding(
                  padding: const EdgeInsets.symmetric(
                      horizontal: 16, vertical: 16),
                  child: Row(
                    children: [
                      // Icon
                      Container(
                        width: 44,
                        height: 44,
                        decoration: BoxDecoration(
                          color: AppColors.iconBg,
                          borderRadius: BorderRadius.circular(12),
                        ),
                        child: Icon(
                          _resolveIcon(),
                          size: 20,
                          color: AppColors.primaryBlue,
                        ),
                      ),

                      const SizedBox(width: 14),

                      // Name + description
                      Expanded(
                        child: Column(
                          crossAxisAlignment: CrossAxisAlignment.start,
                          mainAxisAlignment: MainAxisAlignment.center,
                          children: [
                            Text(
                              addOn.nama,
                              style: GoogleFonts.plusJakartaSans(
                                fontSize: 14,
                                fontWeight: FontWeight.w700,
                                color: AppColors.textPrimary,
                              ),
                            ),
                            const SizedBox(height: 2),
                            Text(
                              addOn.deskripsi,
                              style: GoogleFonts.plusJakartaSans(
                                fontSize: 12,
                                fontWeight: FontWeight.w400,
                                color: AppColors.textSecondary,
                              ),
                            ),
                          ],
                        ),
                      ),

                      const SizedBox(width: 24),

                      // Price
                      Column(
                        crossAxisAlignment: CrossAxisAlignment.end,
                        mainAxisAlignment: MainAxisAlignment.center,
                        children: [
                          Text(
                            'PRICE',
                            style: GoogleFonts.plusJakartaSans(
                              fontSize: 9,
                              fontWeight: FontWeight.w600,
                              letterSpacing: 1.0,
                              color: AppColors.textMuted,
                            ),
                          ),
                          const SizedBox(height: 2),
                          Text(
                            'Rp ${_formatHarga(addOn.harga)}',
                            style: GoogleFonts.plusJakartaSans(
                              fontSize: 15,
                              fontWeight: FontWeight.w700,
                              color: AppColors.textPrimary,
                            ),
                          ),
                        ],
                      ),

                      const SizedBox(width: 20),

                      // Status badge
                      _StatusBadge(addOn: addOn),
                    ],
                  ),
                ),
              ),
            ],
          ),
        ),
      ),
    );
  }

  String _formatHarga(int harga) {
    final s = harga.toString();
    if (s.length > 3) {
      return '${s.substring(0, s.length - 3)}.${s.substring(s.length - 3)}';
    }
    return s;
  }
}

class _StatusBadge extends StatelessWidget {
  final AddOn addOn;
  const _StatusBadge({required this.addOn});

  @override
  Widget build(BuildContext context) {
    final isStock = addOn.statusType == AddOnStatusType.stockLevel;

    return Container(
      constraints: const BoxConstraints(minWidth: 110),
      padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 10),
      decoration: BoxDecoration(
        color: isStock
            ? AppColors.primaryBlue
            : const Color(0xFFEFF2F7),
        borderRadius: BorderRadius.circular(10),
      ),
      child: Column(
        mainAxisSize: MainAxisSize.min,
        children: [
          Text(
            isStock ? 'STOCK LEVEL' : 'STATUS',
            style: GoogleFonts.plusJakartaSans(
              fontSize: 9,
              fontWeight: FontWeight.w600,
              letterSpacing: 0.8,
              color: isStock
                  ? Colors.white.withOpacity(0.8)
                  : AppColors.textMuted,
            ),
          ),
          const SizedBox(height: 3),
          Text(
            isStock ? 'Sisa ${addOn.sisaStok} pcs' : 'Available',
            style: GoogleFonts.plusJakartaSans(
              fontSize: 12.5,
              fontWeight: FontWeight.w700,
              color: isStock ? Colors.white : AppColors.textPrimary,
            ),
          ),
        ],
      ),
    );
  }
}
