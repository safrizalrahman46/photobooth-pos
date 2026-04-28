import 'package:flutter/material.dart';
import '../../../../../app/theme/app_colors.dart';
import '../../../../../app/theme/app_text_styles.dart';
import '../../../domain/entities/booking.dart';

class PackageCard extends StatelessWidget {
  final Package package;
  final bool isSelected;
  final VoidCallback onTap;

  const PackageCard({
    super.key,
    required this.package,
    required this.isSelected,
    required this.onTap,
  });

  IconData get _icon {
    switch (package.id) {
      case 'basic':
        return Icons.camera_alt_rounded;
      case 'mandi_bola':
        return Icons.pool_rounded;
      case 'minimarket':
        return Icons.shopping_cart_rounded;
      default:
        return Icons.photo_camera_rounded;
    }
  }

  String _formatPrice(double price) {
    final int p = price.toInt();
    return 'Rp ${p.toString().replaceAllMapped(RegExp(r'\B(?=(\d{3})+(?!\d))'), (m) => '.')}';
  }

  @override
  Widget build(BuildContext context) {
    return GestureDetector(
      onTap: onTap,
      child: AnimatedContainer(
        duration: const Duration(milliseconds: 200),
        // No fixed width, use constraints for responsiveness
        constraints: const BoxConstraints(maxWidth: 220),
        padding: const EdgeInsets.all(16),
        decoration: BoxDecoration(
          color: isSelected ? AppColors.surface : AppColors.surface,
          borderRadius: BorderRadius.circular(14),
          border: Border.all(
            color: isSelected ? AppColors.primary : AppColors.cardBorder,
            width: isSelected ? 2 : 1,
          ),
          boxShadow: isSelected
              ? [
                  BoxShadow(
                    color: AppColors.primary.withOpacity(0.15),
                    blurRadius: 10,
                    offset: const Offset(0, 3),
                  ),
                ]
              : [
                  BoxShadow(
                    color: Colors.black.withOpacity(0.04),
                    blurRadius: 6,
                    offset: const Offset(0, 2),
                  ),
                ],
        ),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.center,
          children: [
            // Check badge
            Row(
              mainAxisAlignment: MainAxisAlignment.end,
              children: [
                if (isSelected)
                  Container(
                    width: 18,
                    height: 18,
                    decoration: const BoxDecoration(
                      color: AppColors.primary,
                      shape: BoxShape.circle,
                    ),
                    child: const Icon(
                      Icons.check_rounded,
                      size: 12,
                      color: Colors.white,
                    ),
                  )
                else
                  const SizedBox(height: 18),
              ],
            ),
            const SizedBox(height: 4),

            // Icon
            Container(
              width: 48,
              height: 48,
              decoration: BoxDecoration(
                color: isSelected
                    ? AppColors.primaryLight
                    : AppColors.background,
                shape: BoxShape.circle,
              ),
              child: Icon(
                _icon,
                size: 24,
                color: isSelected ? AppColors.primary : AppColors.textSecondary,
              ),
            ),
            const SizedBox(height: 8),

            // Name
            Text(
              package.name,
              style: AppTextStyles.h4.copyWith(
                color: isSelected ? AppColors.primary : AppColors.textPrimary,
              ),
              textAlign: TextAlign.center,
            ),
            const SizedBox(height: 2),
            Text(
              '${package.duration} • ${package.prints}',
              style: AppTextStyles.caption,
              textAlign: TextAlign.center,
            ),
            const SizedBox(height: 8),

            // Price
            Text(
              _formatPrice(package.price),
              style: AppTextStyles.priceSmall.copyWith(
                color: isSelected ? AppColors.primary : AppColors.textPrimary,
              ),
            ),
          ],
        ),
      ),
    );
  }
}
