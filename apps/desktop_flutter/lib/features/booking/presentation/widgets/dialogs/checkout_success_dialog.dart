import 'package:desktop_flutter/app/theme/app_colors.dart';
import 'package:desktop_flutter/app/theme/app_text_styles.dart';
import 'package:desktop_flutter/features/booking/domain/entities/booking.dart';
import 'package:desktop_flutter/shared/models/pos_walk_in_checkout_result.dart';
import 'package:desktop_flutter/shared/widgets/base_dialog.dart';
import 'package:desktop_flutter/shared/widgets/dialog_action_button.dart';
import 'package:flutter/material.dart';

class CheckoutSuccessDialog extends StatelessWidget {
  final PosWalkInCheckoutResult result;
  final Package selectedPackage;
  final VoidCallback onPrint;
  final VoidCallback onDone;

  const CheckoutSuccessDialog({
    super.key,
    required this.result,
    required this.selectedPackage,
    required this.onPrint,
    required this.onDone,
  });

  String _formatPrice(double price) {
    final int p = price.toInt();
    return 'Rp ${p.toString().replaceAllMapped(RegExp(r"\B(?=(\d{3})+(?!\d))"), (m) => ".")}';
  }

  @override
  Widget build(BuildContext context) {
    return BaseDialog(
      width: 480,
      child: SingleChildScrollView(
        child: Column(
          mainAxisSize: MainAxisSize.min,
          children: [
            // Success Icon
            Container(
              padding: const EdgeInsets.all(16),
              decoration: BoxDecoration(
                color: AppColors.primaryLight,
                shape: BoxShape.circle,
              ),
              child: Icon(
                Icons.check_circle,
                color: AppColors.primaryDark,
                size: 48,
              ),
            ),
            const SizedBox(height: 24),

            // Title
            Text(
              'Pembayaran Berhasil!',
              style: AppTextStyles.h1.copyWith(
                color: AppColors.primaryDark,
                fontSize: 22,
              ),
            ),
            const SizedBox(height: 8),

            // Transaction ID
            Text(
              'Transaction ID ${result.transaction.transactionCode}',
              style: AppTextStyles.bodySmall.copyWith(
                fontSize: 12,
                color: AppColors.textSecondary,
              ),
            ),
            const SizedBox(height: 18),

            Container(
              width: double.infinity,
              padding: const EdgeInsets.all(16),
              decoration: BoxDecoration(
                color: AppColors.primaryLight,
                borderRadius: BorderRadius.circular(16),
                border: Border.all(color: AppColors.primaryLight),
              ),
              child: Column(
                children: [
                  Text(
                    'NOMOR ANTREAN',
                    style: AppTextStyles.caption.copyWith(
                      color: AppColors.primaryDark,
                      fontWeight: FontWeight.w800,
                      letterSpacing: 0.8,
                    ),
                  ),
                  const SizedBox(height: 6),
                  Text(
                    result.queueTicket.queueCode,
                    style: AppTextStyles.priceLarge.copyWith(
                      color: AppColors.primaryDark,
                      fontSize: 40,
                    ),
                  ),
                  const SizedBox(height: 4),
                  Text(
                    'Customer sudah masuk antrean dan tampil di queue board.',
                    textAlign: TextAlign.center,
                    style: AppTextStyles.bodySmall.copyWith(
                      color: AppColors.textSecondary,
                    ),
                  ),
                ],
              ),
            ),
            const SizedBox(height: 24),

            // Package Card
            Container(
              padding: const EdgeInsets.all(16),
              decoration: BoxDecoration(
                color: AppColors.cardBg,
                borderRadius: BorderRadius.circular(16),
              ),
              child: Row(
                children: [
                  // Package Image Placeholder
                  Container(
                    width: 60,
                    height: 60,
                    decoration: BoxDecoration(
                      color: Colors.white,
                      borderRadius: BorderRadius.circular(8),
                    ),
                    child: const Icon(
                      Icons.image,
                      color: Colors.grey,
                      size: 24,
                    ),
                  ),
                  const SizedBox(width: 16),

                  // Package Details
                  Expanded(
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        Text(
                          'PAKET TERPILIH',
                          style: AppTextStyles.label.copyWith(
                            fontSize: 8,
                            color: AppColors.primary,
                          ),
                        ),
                        const SizedBox(height: 4),
                        Text(
                          selectedPackage.name,
                          style: AppTextStyles.h3.copyWith(
                            fontSize: 14,
                            fontWeight: FontWeight.w700,
                          ),
                        ),
                        Text(
                          '${selectedPackage.duration} • Package',
                          style: AppTextStyles.caption.copyWith(fontSize: 10),
                        ),
                      ],
                    ),
                  ),

                  // Price & Badge
                  Column(
                    crossAxisAlignment: CrossAxisAlignment.end,
                    children: [
                      Text(
                        _formatPrice(result.transaction.totalAmount),
                        style: AppTextStyles.priceSmall.copyWith(
                          fontSize: 14,
                          fontWeight: FontWeight.w800,
                        ),
                      ),
                      const SizedBox(height: 4),
                      Container(
                        padding: const EdgeInsets.symmetric(
                          horizontal: 8,
                          vertical: 2,
                        ),
                        decoration: BoxDecoration(
                          color: AppColors.success.withValues(alpha: 0.1),
                          borderRadius: BorderRadius.circular(4),
                        ),
                        child: Text(
                          'LUNAS',
                          style: const TextStyle(
                            fontSize: 8,
                            fontWeight: FontWeight.w700,
                            color: AppColors.success,
                          ),
                        ),
                      ),
                    ],
                  ),
                ],
              ),
            ),
            const SizedBox(height: 32),

            // Buttons Row
            Row(
              children: [
                Expanded(
                  child: DialogActionButton(
                    label: 'CETAK STRUK',
                    icon: Icons.print_outlined,
                    onPressed: onPrint,
                  ),
                ),
                const SizedBox(width: 12),
                Expanded(
                  child: DialogActionButton(
                    label: 'SELESAI',
                    primary: true,
                    color: AppColors.primaryDark,
                    icon: Icons.check_rounded,
                    onPressed: onDone,
                  ),
                ),
              ],
            ),
            const SizedBox(height: 24),

            // Footer Link
            TextButton(
              onPressed: onDone,
              child: Text(
                'Kembali ke Menu Utama',
                style: AppTextStyles.captionMedium.copyWith(
                  decoration: TextDecoration.underline,
                  color: AppColors.textSecondary,
                ),
              ),
            ),
          ],
        ),
      ),
    );
  }
}
