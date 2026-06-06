import 'package:flutter/material.dart';
import 'package:desktop_flutter/app/theme/app_colors.dart';
import 'package:desktop_flutter/app/theme/app_text_styles.dart';
import 'package:desktop_flutter/shared/widgets/base_dialog.dart';

class LogoutDialog extends StatelessWidget {
  const LogoutDialog({super.key});

  @override
  Widget build(BuildContext context) {
    return BaseDialog(
      width: 420,
      child: Column(
        mainAxisSize: MainAxisSize.min,
        children: [
          Row(
            children: [
              Container(
                padding: const EdgeInsets.all(8),
                decoration: BoxDecoration(
                  color: AppColors.error.withValues(alpha: 0.08),
                  borderRadius: BorderRadius.circular(10),
                ),
                child: const Icon(
                  Icons.logout_rounded,
                  color: AppColors.error,
                  size: 20,
                ),
              ),
              const SizedBox(width: 12),
              Text(
                'Keluar',
                style: AppTextStyles.h2,
              ),
            ],
          ),
          const SizedBox(height: 16),
          Text(
            'Yakin ingin keluar dari aplikasi desktop?',
            style: AppTextStyles.body.copyWith(
              color: AppColors.textSecondary,
            ),
          ),
          const SizedBox(height: 24),
          Row(
            mainAxisAlignment: MainAxisAlignment.end,
            children: [
              OutlinedButton(
                onPressed: () => Navigator.of(context).pop(false),
                style: OutlinedButton.styleFrom(
                  padding: const EdgeInsets.symmetric(
                    horizontal: 20,
                    vertical: 14,
                  ),
                  shape: RoundedRectangleBorder(
                    borderRadius: BorderRadius.circular(12),
                  ),
                ),
                child: Text(
                  'Batal',
                  style: AppTextStyles.bodyMedium.copyWith(
                    color: AppColors.textSecondary,
                    fontWeight: FontWeight.w600,
                  ),
                ),
              ),
              const SizedBox(width: 8),
              ElevatedButton(
                onPressed: () => Navigator.of(context).pop(true),
                style: ElevatedButton.styleFrom(
                  backgroundColor: AppColors.error,
                  foregroundColor: Colors.white,
                  elevation: 0,
                  padding: const EdgeInsets.symmetric(
                    horizontal: 24,
                    vertical: 14,
                  ),
                  shape: RoundedRectangleBorder(
                    borderRadius: BorderRadius.circular(12),
                  ),
                ),
                child: Text(
                  'Keluar',
                  style: AppTextStyles.bodyMedium.copyWith(
                    color: Colors.white,
                    fontWeight: FontWeight.w600,
                  ),
                ),
              ),
            ],
          ),
        ],
      ),
    );
  }
}
