import 'package:flutter/material.dart';
import '../../../../../app/theme/app_colors.dart';
import '../../../../../app/theme/app_text_styles.dart';
import '../../../application/booking_controller.dart';

class OrderSummaryPanel extends StatelessWidget {
  final BookingController controller;
  final VoidCallback? onConfirm;

  const OrderSummaryPanel({super.key, required this.controller, this.onConfirm});

  String _formatPrice(double price) {
    final int p = price.toInt();
    return 'Rp ${p.toString().replaceAllMapped(RegExp(r'\B(?=(\d{3})+(?!\d))'), (m) => '.')}';
  }

  @override
  Widget build(BuildContext context) {
    return Container(
      width: 320,
      decoration: BoxDecoration(
        color: AppColors.panelBg,
        borderRadius: BorderRadius.circular(20),
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          // Header
          Padding(
            padding: const EdgeInsets.fromLTRB(16, 16, 16, 12),
            child: Text(
              'ORDER SUMMARY',
              style: AppTextStyles.captionMedium.copyWith(
                color: AppColors.textPrimary,
                letterSpacing: 1.2,
                fontSize: 15,
              ),
            ),
          ),

          // Divider
          Container(height: 4, color: Colors.black.withOpacity(0.2)),

          Expanded(
            child: SingleChildScrollView(
              padding: const EdgeInsets.only(top: 12, bottom: 14),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  // Package item
                  _OrderSummaryRow(
                    title: '${controller.selectedPackage.name} Package',
                    subtitle: '${controller.selectedPackage.duration} Session',
                    price: _formatPrice(controller.packagePrice),
                  ),

                  // Addon items
                  if (controller.selectedAddons.isNotEmpty)
                    Padding(
                      padding: const EdgeInsets.fromLTRB(14, 0, 14, 10),
                      child: Container(
                        padding: const EdgeInsets.symmetric(vertical: 8),
                        decoration: BoxDecoration(
                          color: Colors.black.withOpacity(0.15),
                          borderRadius: BorderRadius.circular(8),
                        ),
                        child: Column(
                          children: [
                            for (int i = 0; i < controller.selectedAddons.length; i++) ...[
                              _OrderSummaryItem(
                                title: controller.selectedAddons[i].name,
                                subtitle: 'x${controller.selectedAddons[i].quantity}',
                                price: _formatPrice(controller.selectedAddons[i].price * controller.selectedAddons[i].quantity),
                              ),
                              if (i < controller.selectedAddons.length - 1)
                                Padding(
                                  padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 4),
                                  child: Divider(color: Colors.white.withOpacity(0.1), height: 1),
                                ),
                            ],
                          ],
                        ),
                      ),
                    ),

                  const SizedBox(height: 8),

                  // Voucher
                  Padding(
                    padding: const EdgeInsets.symmetric(horizontal: 14),
                    child: Container(
                      padding: const EdgeInsets.all(12),
                      decoration: BoxDecoration(
                        color: Colors.black.withOpacity(0.15),
                        borderRadius: BorderRadius.circular(10),
                        border: Border.all(color: Colors.white.withOpacity(0.1)),
                      ),
                      child: Column(
                        crossAxisAlignment: CrossAxisAlignment.start,
                        children: [
                          Row(
                            children: [
                              const Icon(Icons.confirmation_num_outlined, color: Colors.white, size: 14),
                              const SizedBox(width: 6),
                              Text(
                                'VOUCHER / DISKON',
                                style: AppTextStyles.caption.copyWith(
                                  color: Colors.white.withOpacity(0.9),
                                  letterSpacing: 0.8,
                                  fontSize: 10,
                                  fontWeight: FontWeight.w700,
                                ),
                              ),
                            ],
                          ),
                          const SizedBox(height: 10),
                          Row(
                            children: [
                              Expanded(
                                child: Container(
                                  padding: const EdgeInsets.symmetric(
                                    horizontal: 12,
                                    vertical: 10,
                                  ),
                                  decoration: BoxDecoration(
                                    color: Colors.black.withOpacity(0.2),
                                    borderRadius: const BorderRadius.only(
                                      topLeft: Radius.circular(8),
                                      bottomLeft: Radius.circular(8),
                                    ),
                                  ),
                                  child: Text(
                                    controller.voucherCode.isEmpty ? 'Masukkan kode voucher' : controller.voucherCode,
                                    style: AppTextStyles.bodySmall.copyWith(
                                      color: controller.voucherCode.isEmpty ? Colors.white.withOpacity(0.4) : Colors.white,
                                      fontSize: 11,
                                    ),
                                  ),
                                ),
                              ),
                              GestureDetector(
                                onTap: controller.applyVoucher,
                                child: Container(
                                  padding: const EdgeInsets.symmetric(
                                    horizontal: 16,
                                    vertical: 10,
                                  ),
                                  decoration: const BoxDecoration(
                                    color: Colors.white,
                                    borderRadius: BorderRadius.only(
                                      topRight: Radius.circular(8),
                                      bottomRight: Radius.circular(8),
                                    ),
                                  ),
                                  child: Text(
                                    'APPLY',
                                    style: AppTextStyles.captionMedium.copyWith(
                                      color: AppColors.primary,
                                      fontSize: 11,
                                      fontWeight: FontWeight.w800,
                                    ),
                                  ),
                                ),
                              ),
                            ],
                          ),
                        ],
                      ),
                    ),
                  ),

                  const SizedBox(height: 14),
                ],
              ),
            ),
          ),

          // Grand total
          Padding(
            padding: const EdgeInsets.symmetric(horizontal: 14),
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Row(
                  children: [
                    Text(
                      'Grand Total',
                      style: AppTextStyles.captionMedium.copyWith(fontSize: 11, color: AppColors.textPrimary),
                    ),
                    const SizedBox(width: 8),
                    Container(
                      padding: const EdgeInsets.symmetric(
                        horizontal: 8,
                        vertical: 2,
                      ),
                      decoration: BoxDecoration(
                        color: AppColors.lunas,
                        borderRadius: BorderRadius.circular(5),
                      ),
                      child: Text(
                        'LUNAS',
                        style: AppTextStyles.caption.copyWith(
                          color: Colors.white,
                          fontWeight: FontWeight.w700,
                          fontSize: 9,
                        ),
                      ),
                    ),
                  ],
                ),
                const SizedBox(height: 2),
                Text(
                  _formatPrice(controller.grandTotal),
                  style: AppTextStyles.priceLarge.copyWith(color: AppColors.textPrimary),
                ),
              ],
            ),
          ),

          const SizedBox(height: 14),

          // Payment method toggle
          Padding(
            padding: const EdgeInsets.symmetric(horizontal: 14),
            child: Container(
              decoration: BoxDecoration(
                color: Colors.black.withOpacity(0.05),
                borderRadius: BorderRadius.circular(10),
              ),
              child: Row(
                children: [
                  _PaymentTab(
                    label: 'TUNAI',
                    isSelected: controller.selectedPayment == 'TUNAI',
                    onTap: () => controller.setPayment('TUNAI'),
                  ),
                  _PaymentTab(
                    label: 'QRIS',
                    isSelected: controller.selectedPayment == 'QRIS',
                    onTap: () => controller.setPayment('QRIS'),
                  ),
                ],
              ),
            ),
          ),

          const SizedBox(height: 10),

          // Confirm button
          Padding(
            padding: const EdgeInsets.fromLTRB(14, 0, 14, 16),
            child: GestureDetector(
              onTap: controller.isSubmitting || onConfirm == null
                  ? null
                  : () {
                      onConfirm!();
                    },
              child: Container(
                width: double.infinity,
                padding: const EdgeInsets.symmetric(vertical: 14),
                decoration: BoxDecoration(
                  color: Colors.white,
                  borderRadius: BorderRadius.circular(12),
                  boxShadow: [
                    BoxShadow(
                      color: Colors.black.withOpacity(0.1),
                      blurRadius: 8,
                      offset: const Offset(0, 2),
                    ),
                  ],
                ),
                child: Text(
                  controller.isSubmitting ? 'MEMPROSES...' : 'KONFIRMASI &\nPEMBAYARAN',
                  style: AppTextStyles.h4.copyWith(
                    color: AppColors.textPrimary,
                    fontWeight: FontWeight.w700,
                  ),
                  textAlign: TextAlign.center,
                ),
              ),
            ),
          ),
        ],
      ),
    );
  }
}

class _OrderSummaryRow extends StatelessWidget {
  final String title;
  final String subtitle;
  final String price;

  const _OrderSummaryRow({
    required this.title,
    required this.subtitle,
    required this.price,
  });

  @override
  Widget build(BuildContext context) {
    return Padding(
      padding: const EdgeInsets.fromLTRB(14, 0, 14, 10),
      child: Container(
        padding: const EdgeInsets.symmetric(vertical: 12),
        decoration: BoxDecoration(
          color: Colors.black.withOpacity(0.15),
          borderRadius: BorderRadius.circular(8),
        ),
        child: _OrderSummaryItem(
          title: title,
          subtitle: subtitle,
          price: price,
        ),
      ),
    );
  }
}

class _OrderSummaryItem extends StatelessWidget {
  final String title;
  final String subtitle;
  final String price;

  const _OrderSummaryItem({
    required this.title,
    required this.subtitle,
    required this.price,
  });

  @override
  Widget build(BuildContext context) {
    return Padding(
      padding: const EdgeInsets.symmetric(horizontal: 14),
      child: Row(
        crossAxisAlignment: CrossAxisAlignment.center,
        children: [
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(
                  title,
                  style: AppTextStyles.bodyMedium.copyWith(
                    color: Colors.white,
                    fontWeight: FontWeight.w600,
                    fontSize: 12,
                  ),
                ),
                const SizedBox(height: 2),
                Text(
                  subtitle,
                  style: AppTextStyles.captionMedium.copyWith(
                    color: Colors.white.withOpacity(0.7),
                    fontSize: 10,
                  ),
                ),
              ],
            ),
          ),
          Text(
            price,
            style: AppTextStyles.bodyMedium.copyWith(
              color: Colors.white,
              fontWeight: FontWeight.w700,
              fontSize: 12,
            ),
          ),
        ],
      ),
    );
  }
}

class _PaymentTab extends StatelessWidget {
  final String label;
  final bool isSelected;
  final VoidCallback onTap;

  const _PaymentTab({
    required this.label,
    required this.isSelected,
    required this.onTap,
  });

  @override
  Widget build(BuildContext context) {
    return Expanded(
      child: GestureDetector(
        onTap: onTap,
        child: Container(
          padding: const EdgeInsets.symmetric(vertical: 8),
          decoration: BoxDecoration(
            color: isSelected ? Colors.white : Colors.transparent,
            borderRadius: BorderRadius.circular(10),
          ),
          child: Text(
            label,
            style: AppTextStyles.captionMedium.copyWith(
              color: isSelected
                  ? AppColors.textPrimary
                  : AppColors.textSecondary,
              fontWeight: FontWeight.w700,
              fontSize: 11,
            ),
            textAlign: TextAlign.center,
          ),
        ),
      ),
    );
  }
}
