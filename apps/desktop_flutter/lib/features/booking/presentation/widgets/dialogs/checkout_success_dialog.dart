import 'package:desktop_flutter/app/theme/app_colors.dart';
import 'package:desktop_flutter/app/theme/app_text_styles.dart';
import 'package:desktop_flutter/features/booking/domain/entities/booking.dart';
import 'package:desktop_flutter/shared/models/pos_walk_in_checkout_result.dart';
import 'package:desktop_flutter/shared/widgets/base_dialog.dart';
import 'package:desktop_flutter/shared/widgets/dialog_action_button.dart';
import 'package:flutter/material.dart';
import 'package:desktop_flutter/core/session/api_session.dart';
import 'ubah_metode_dialog.dart';

class CheckoutSuccessDialog extends StatefulWidget {
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

  @override
  State<CheckoutSuccessDialog> createState() => _CheckoutSuccessDialogState();
}

class _CheckoutSuccessDialogState extends State<CheckoutSuccessDialog> {
  late String _currentPaymentMethod;
  bool _updating = false;

  @override
  void initState() {
    super.initState();
    final payments = widget.result.transaction.payments;
    _currentPaymentMethod = payments.isNotEmpty ? payments.first.method : 'TUNAI';
  }

  String _formatPrice(double price) {
    final int p = price.toInt();
    return 'Rp ${p.toString().replaceAllMapped(RegExp(r"\B(?=(\d{3})+(?!\d))"), (m) => ".")}';
  }

  Future<void> _changePaymentMethod(BuildContext context) async {
    final payments = widget.result.transaction.payments;
    if (payments.isEmpty) {
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(content: Text('Tidak ada data pembayaran untuk transaksi ini.')),
      );
      return;
    }

    final payment = payments.first;
    final res = await showDialog<Map<String, String>>(
      context: context,
      builder: (context) => UbahMetodeDialog(currentMethod: _currentPaymentMethod),
    );

    if (res == null) return;

    final client = ApiSession.client;
    if (client == null) return;

    setState(() => _updating = true);
    try {
      final success = await client.updatePaymentMethod(
        paymentId: payment.id,
        method: res['method']!,
        reason: res['reason']!,
      );

      if (success && mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          const SnackBar(content: Text('Metode pembayaran berhasil diubah.')),
        );
        setState(() {
          _currentPaymentMethod = res['method']!;
        });
        // Automatically reprint the receipt
        widget.onPrint();
      } else if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          const SnackBar(content: Text('Gagal mengubah metode pembayaran.')),
        );
      }
    } catch (_) {
      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          const SnackBar(content: Text('Terjadi kesalahan saat mengubah metode pembayaran.')),
        );
      }
    } finally {
      if (mounted) {
        setState(() => _updating = false);
      }
    }
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
              'Transaction ID ${widget.result.transaction.transactionCode}',
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
                    widget.result.queueTicket.queueCode,
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
                          widget.selectedPackage.name,
                          style: AppTextStyles.h3.copyWith(
                            fontSize: 14,
                            fontWeight: FontWeight.w700,
                          ),
                        ),
                        Text(
                          '${widget.selectedPackage.duration} • Package',
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
                        _formatPrice(widget.result.transaction.totalAmount),
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
                          _currentPaymentMethod.toUpperCase(),
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
                    label: 'SELESAI',
                    primary: true,
                    color: AppColors.primaryDark,
                    icon: Icons.check_rounded,
                    onPressed: widget.onDone,
                  ),
                ),
              ],
            ),
            const SizedBox(height: 16),

            // Owner only Payment Method Modification
            if (ApiSession.current?.user.hasRole('owner') == true) ...[
              SizedBox(
                width: double.infinity,
                child: OutlinedButton.icon(
                  onPressed: _updating ? null : () => _changePaymentMethod(context),
                  icon: _updating
                      ? const SizedBox(
                          width: 14,
                          height: 14,
                          child: CircularProgressIndicator(strokeWidth: 2, color: AppColors.primary),
                        )
                      : const Icon(Icons.edit_note_rounded, size: 16),
                  label: const Text('UBAH METODE PEMBAYARAN'),
                  style: OutlinedButton.styleFrom(
                    foregroundColor: AppColors.primary,
                    side: const BorderSide(color: AppColors.primary),
                    padding: const EdgeInsets.symmetric(vertical: 12),
                    shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
                  ),
                ),
              ),
              const SizedBox(height: 16),
            ],

            // Subtle fallback Print Button
            TextButton.icon(
              onPressed: widget.onPrint,
              icon: const Icon(
                Icons.print_outlined,
                size: 14,
                color: AppColors.textSecondary,
              ),
              label: Text(
                'Cetak Ulang Nota (Cadangan jika gagal)',
                style: AppTextStyles.captionMedium.copyWith(
                  color: AppColors.textSecondary,
                  fontSize: 10,
                ),
              ),
            ),
            const SizedBox(height: 16),

            // Footer Link
            TextButton(
              onPressed: widget.onDone,
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
