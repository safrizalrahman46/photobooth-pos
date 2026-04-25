import 'package:flutter/material.dart';
import '../../../../../app/theme/app_colors.dart';
import '../../../../../app/theme/app_text_styles.dart';
import '../../../domain/entities/booking.dart';

class QueueCard extends StatelessWidget {
  final Booking booking;
  final bool isActive;
  final VoidCallback onTap;

  const QueueCard({
    super.key,
    required this.booking,
    required this.isActive,
    required this.onTap,
  });

  @override
  Widget build(BuildContext context) {
    return GestureDetector(
      onTap: onTap,
      child: AnimatedContainer(
        duration: const Duration(milliseconds: 200),
        margin: const EdgeInsets.only(bottom: 8),
        padding: const EdgeInsets.all(12),
        decoration: BoxDecoration(
          color: isActive ? AppColors.queueActive : AppColors.surface,
          borderRadius: BorderRadius.circular(12),
          border: Border.all(
            color: isActive
                ? AppColors.queueActiveBorder
                : AppColors.cardBorder,
            width: isActive ? 1.5 : 1,
          ),
          boxShadow: isActive
              ? [
                  BoxShadow(
                    color: AppColors.primary.withOpacity(0.2),
                    blurRadius: 8,
                    offset: const Offset(0, 2),
                  ),
                ]
              : [],
        ),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Row(
              mainAxisAlignment: MainAxisAlignment.spaceBetween,
              children: [
                Text(
                  booking.id,
                  style: AppTextStyles.captionMedium.copyWith(
                    color: isActive
                        ? AppColors.textWhite.withOpacity(0.85)
                        : AppColors.textMuted,
                    fontWeight: FontWeight.w700,
                  ),
                ),
                Text(
                  booking.time,
                  style: AppTextStyles.caption.copyWith(
                    color: isActive
                        ? AppColors.textWhite.withOpacity(0.75)
                        : AppColors.textMuted,
                    fontWeight: isActive ? FontWeight.w600 : FontWeight.w400,
                  ),
                ),
              ],
            ),
            const SizedBox(height: 4),
            Text(
              booking.customerName,
              style: isActive
                  ? AppTextStyles.h4.copyWith(color: AppColors.textWhite)
                  : AppTextStyles.h4,
            ),
            const SizedBox(height: 2),
            Text(
              booking.phone,
              style: AppTextStyles.caption.copyWith(
                color: isActive
                    ? AppColors.textWhite.withOpacity(0.75)
                    : AppColors.textMuted,
              ),
            ),
            const SizedBox(height: 6),
            Container(
              padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 3),
              decoration: BoxDecoration(
                color: isActive
                    ? Colors.white.withOpacity(0.2)
                    : AppColors.primaryLight,
                borderRadius: BorderRadius.circular(6),
              ),
              child: Text(
                booking.status,
                style: AppTextStyles.caption.copyWith(
                  color: isActive ? AppColors.textWhite : AppColors.primary,
                  fontWeight: FontWeight.w700,
                  fontSize: 9,
                  letterSpacing: 0.5,
                ),
              ),
            ),
          ],
        ),
      ),
    );
  }
}
