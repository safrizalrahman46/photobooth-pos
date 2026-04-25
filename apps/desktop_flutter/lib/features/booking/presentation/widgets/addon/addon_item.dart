import 'package:flutter/material.dart';
import '../../../../../app/theme/app_colors.dart';
import '../../../../../app/theme/app_text_styles.dart';
import '../../../domain/entities/booking.dart';

class AddonItem extends StatelessWidget {
  final Addon addon;
  final int index;
  final VoidCallback onIncrement;
  final VoidCallback onDecrement;

  const AddonItem({
    super.key,
    required this.addon,
    required this.index,
    required this.onIncrement,
    required this.onDecrement,
  });

  IconData get _icon {
    switch (addon.id) {
      case 'cetak_4r':
        return Icons.print_rounded;
      case 'frame_custom':
        return Icons.crop_rounded;
      case 'extra_person':
        return Icons.person_add_rounded;
      default:
        return Icons.add_circle_outline_rounded;
    }
  }

  String _formatPrice(double price) {
    final int p = price.toInt();
    return 'Rp ${p.toString().replaceAllMapped(RegExp(r'\B(?=(\d{3})+(?!\d))'), (m) => '.')}';
  }

  @override
  Widget build(BuildContext context) {
    final bool hasQty = addon.quantity > 0;

    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 12),
      decoration: BoxDecoration(
        color: AppColors.surface,
        borderRadius: BorderRadius.circular(12),
        border: Border.all(color: AppColors.cardBorder),
      ),
      child: Row(
        children: [
          // Icon
          Container(
            width: 38,
            height: 38,
            decoration: BoxDecoration(
              color: AppColors.primaryLight,
              borderRadius: BorderRadius.circular(10),
            ),
            child: Icon(_icon, size: 18, color: AppColors.primary),
          ),
          const SizedBox(width: 12),

          // Text
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(addon.name, style: AppTextStyles.h4),
                const SizedBox(height: 2),
                Text(addon.subtitle, style: AppTextStyles.bodySmall),
              ],
            ),
          ),

          // Price
          Text(_formatPrice(addon.price), style: AppTextStyles.priceSmall),
          const SizedBox(width: 16),

          // Quantity control
          if (hasQty)
            Row(
              children: [
                _CircleButton(
                  icon: Icons.remove_rounded,
                  onTap: onDecrement,
                  outlined: true,
                ),
                Padding(
                  padding: const EdgeInsets.symmetric(horizontal: 10),
                  child: Text(
                    '${addon.quantity}',
                    style: AppTextStyles.bodyMedium.copyWith(
                      fontWeight: FontWeight.w700,
                    ),
                  ),
                ),
                _CircleButton(
                  icon: Icons.add_rounded,
                  onTap: onIncrement,
                  outlined: false,
                ),
              ],
            )
          else
            GestureDetector(
              onTap: onIncrement,
              child: Container(
                padding: const EdgeInsets.symmetric(
                  horizontal: 18,
                  vertical: 7,
                ),
                decoration: BoxDecoration(
                  color: AppColors.primary,
                  borderRadius: BorderRadius.circular(8),
                ),
                child: Text(
                  'ADD',
                  style: AppTextStyles.captionMedium.copyWith(
                    color: Colors.white,
                    fontSize: 11,
                  ),
                ),
              ),
            ),
        ],
      ),
    );
  }
}

class _CircleButton extends StatelessWidget {
  final IconData icon;
  final VoidCallback onTap;
  final bool outlined;

  const _CircleButton({
    required this.icon,
    required this.onTap,
    required this.outlined,
  });

  @override
  Widget build(BuildContext context) {
    return GestureDetector(
      onTap: onTap,
      child: Container(
        width: 28,
        height: 28,
        decoration: BoxDecoration(
          color: outlined ? Colors.transparent : AppColors.primary,
          shape: BoxShape.circle,
          border: Border.all(
            color: outlined ? AppColors.cardBorder : AppColors.primary,
          ),
        ),
        child: Icon(
          icon,
          size: 14,
          color: outlined ? AppColors.textSecondary : Colors.white,
        ),
      ),
    );
  }
}
