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
                    horizontal: 16,
                    vertical: 16,
                  ),
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
                              maxLines: 2,
                              overflow: TextOverflow.ellipsis,
                              style: GoogleFonts.plusJakartaSans(
                                fontSize: 12,
                                fontWeight: FontWeight.w400,
                                color: AppColors.textSecondary,
                              ),
                            ),
                          ],
                        ),
                      ),
                      // PRICE & STATUS REMOVED (AS REQUESTED)
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
}
