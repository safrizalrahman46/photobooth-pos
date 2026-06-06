import 'package:flutter/material.dart';
import 'package:desktop_flutter/app/theme/app_colors.dart';
import 'package:desktop_flutter/app/theme/app_text_styles.dart';
import 'package:desktop_flutter/shared/models/walk_in_request_item.dart';
import 'package:desktop_flutter/shared/widgets/base_dialog.dart';

class PaymentConfirmDialog extends StatelessWidget {
  final WalkInRequestItem item;

  const PaymentConfirmDialog({super.key, required this.item});

  String _formatPrice(double value) {
    final rounded = value.round();
    final chars = rounded.toString().split('').reversed.toList();
    final buffer = StringBuffer();

    for (var i = 0; i < chars.length; i++) {
      if (i > 0 && i % 3 == 0) {
        buffer.write('.');
      }
      buffer.write(chars[i]);
    }

    return 'Rp ${buffer.toString().split('').reversed.join()}';
  }

  @override
  Widget build(BuildContext context) {
    return BaseDialog(
      width: 420,
      child: Column(
        mainAxisSize: MainAxisSize.min,
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Container(
            padding: const EdgeInsets.all(12),
            decoration: BoxDecoration(
              color: AppColors.warning.withValues(alpha: 0.1),
              borderRadius: BorderRadius.circular(16),
            ),
            child: const Icon(
              Icons.payments_rounded,
              color: AppColors.warning,
              size: 28,
            ),
          ),
          const SizedBox(height: 18),
          Text('Konfirmasi Pembayaran Tunai', style: AppTextStyles.h2),
          const SizedBox(height: 8),
          Text(
            'Pastikan ${item.customerName} sudah membayar ${_formatPrice(item.totalAmount)}. Setelah dikonfirmasi, transaksi dan antrean akan dibuat.',
            style: AppTextStyles.body.copyWith(
              color: AppColors.textSecondary,
              height: 1.5,
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
                child: const Text('Batal'),
              ),
              const SizedBox(width: 12),
              ElevatedButton(
                onPressed: () => Navigator.of(context).pop(true),
                style: ElevatedButton.styleFrom(
                  backgroundColor: AppColors.success,
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
                child: const Text('Sudah Bayar'),
              ),
            ],
          ),
        ],
      ),
    );
  }
}
