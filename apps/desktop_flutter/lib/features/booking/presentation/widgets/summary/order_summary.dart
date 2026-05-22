import 'package:flutter/material.dart';
import '../../../../../app/theme/app_colors.dart';
import '../../../../../app/theme/app_text_styles.dart';
import '../../../application/booking_controller.dart';

class OrderSummaryPanel extends StatelessWidget {
  final BookingController controller;
  final VoidCallback? onConfirm;

  const OrderSummaryPanel({
    super.key,
    required this.controller,
    this.onConfirm,
  });

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
              'RINGKASAN PESANAN',
              style: AppTextStyles.captionMedium.copyWith(
                color: AppColors.textPrimary,
                letterSpacing: 1.2,
                fontSize: 15,
              ),
            ),
          ),

          // Divider
          Container(height: 4, color: Colors.black.withValues(alpha: 0.2)),

          Expanded(
            child: SingleChildScrollView(
              padding: const EdgeInsets.only(top: 12, bottom: 14),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  // Package item
                  _OrderSummaryRow(
                    title: 'Paket ${controller.selectedPackage.name}',
                    subtitle: 'Durasi ${controller.selectedPackage.duration}',
                    price: _formatPrice(controller.packagePrice),
                  ),

                  // Addon items
                  if (controller.selectedAddons.isNotEmpty)
                    Padding(
                      padding: const EdgeInsets.fromLTRB(14, 0, 14, 10),
                      child: Container(
                        padding: const EdgeInsets.symmetric(vertical: 8),
                        decoration: BoxDecoration(
                          color: Colors.black.withValues(alpha: 0.15),
                          borderRadius: BorderRadius.circular(8),
                        ),
                        child: Column(
                          children: [
                            for (
                              int i = 0;
                              i < controller.selectedAddons.length;
                              i++
                            ) ...[
                              _OrderSummaryItem(
                                title: controller.selectedAddons[i].name,
                                subtitle:
                                    'x${controller.selectedAddons[i].quantity}',
                                price: _formatPrice(
                                  controller.selectedAddons[i].price *
                                      controller.selectedAddons[i].quantity,
                                ),
                              ),
                              if (i < controller.selectedAddons.length - 1)
                                Padding(
                                  padding: const EdgeInsets.symmetric(
                                    horizontal: 14,
                                    vertical: 4,
                                  ),
                                  child: Divider(
                                    color: Colors.white.withValues(alpha: 0.1),
                                    height: 1,
                                  ),
                                ),
                            ],
                          ],
                        ),
                      ),
                    ),

                  const SizedBox(height: 8),

                  // Referral
                  Padding(
                    padding: const EdgeInsets.symmetric(horizontal: 14),
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        Text(
                          'KODE REFERAL',
                          style: AppTextStyles.caption.copyWith(
                            color: AppColors.textSecondary,
                            letterSpacing: 0.8,
                            fontSize: 9,
                          ),
                        ),
                        const SizedBox(height: 6),
                        Row(
                          children: [
                            Expanded(
                              child: TextFormField(
                                initialValue: controller.referralCode,
                                textCapitalization:
                                    TextCapitalization.characters,
                                onChanged: controller.updateReferralCode,
                                decoration: InputDecoration(
                                  isDense: true,
                                  hintText: 'CONTOH10',
                                  filled: true,
                                  fillColor: Colors.black.withValues(
                                    alpha: 0.05,
                                  ),
                                  contentPadding: const EdgeInsets.symmetric(
                                    horizontal: 10,
                                    vertical: 9,
                                  ),
                                  border: const OutlineInputBorder(
                                    borderSide: BorderSide.none,
                                    borderRadius: BorderRadius.only(
                                      topLeft: Radius.circular(8),
                                      bottomLeft: Radius.circular(8),
                                    ),
                                  ),
                                ),
                                style: AppTextStyles.bodySmall.copyWith(
                                  color: AppColors.textPrimary,
                                  fontSize: 11,
                                ),
                              ),
                            ),
                            GestureDetector(
                              onTap: controller.isApplyingReferral
                                  ? null
                                  : () {
                                      controller.applyReferral();
                                    },
                              child: Container(
                                padding: const EdgeInsets.symmetric(
                                  horizontal: 10,
                                  vertical: 7,
                                ),
                                decoration: BoxDecoration(
                                  color: Colors.white,
                                  borderRadius: const BorderRadius.only(
                                    topRight: Radius.circular(8),
                                    bottomRight: Radius.circular(8),
                                  ),
                                ),
                                child: Text(
                                  controller.isApplyingReferral
                                      ? '...'
                                      : 'TERAPKAN',
                                  style: AppTextStyles.captionMedium.copyWith(
                                    color: AppColors.primary,
                                    fontSize: 10,
                                  ),
                                ),
                              ),
                            ),
                          ],
                        ),
                        if (controller.referralMessage != null) ...[
                          const SizedBox(height: 6),
                          Text(
                            controller.referralMessage!,
                            style: AppTextStyles.caption.copyWith(
                              color: const Color(0xFF059669),
                              fontSize: 9,
                            ),
                          ),
                        ],
                        if (controller.referralError != null) ...[
                          const SizedBox(height: 6),
                          Text(
                            controller.referralError!,
                            style: AppTextStyles.caption.copyWith(
                              color: const Color(0xFFDC2626),
                              fontSize: 9,
                            ),
                          ),
                        ],
                      ],
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
                      'Total Bayar',
                      style: AppTextStyles.captionMedium.copyWith(
                        fontSize: 11,
                        color: AppColors.textPrimary,
                      ),
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
                if (controller.referralDiscount > 0) ...[
                  Row(
                    mainAxisAlignment: MainAxisAlignment.spaceBetween,
                    children: [
                      Text(
                        'Subtotal',
                        style: AppTextStyles.caption.copyWith(
                          color: AppColors.textSecondary,
                          fontSize: 10,
                        ),
                      ),
                      Text(
                        _formatPrice(controller.subtotalTotal),
                        style: AppTextStyles.caption.copyWith(
                          color: AppColors.textSecondary,
                          fontSize: 10,
                        ),
                      ),
                    ],
                  ),
                  const SizedBox(height: 2),
                  Row(
                    mainAxisAlignment: MainAxisAlignment.spaceBetween,
                    children: [
                      Text(
                        'Diskon Referal',
                        style: AppTextStyles.caption.copyWith(
                          color: const Color(0xFF059669),
                          fontSize: 10,
                        ),
                      ),
                      Text(
                        '-${_formatPrice(controller.referralDiscount)}',
                        style: AppTextStyles.caption.copyWith(
                          color: const Color(0xFF059669),
                          fontSize: 10,
                        ),
                      ),
                    ],
                  ),
                  const SizedBox(height: 4),
                ],
                Text(
                  _formatPrice(controller.grandTotal),
                  style: AppTextStyles.priceLarge.copyWith(
                    color: AppColors.textPrimary,
                  ),
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
                color: Colors.black.withValues(alpha: 0.05),
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
                      color: Colors.black.withValues(alpha: 0.1),
                      blurRadius: 8,
                      offset: const Offset(0, 2),
                    ),
                  ],
                ),
                child: Text(
                  controller.isSubmitting
                      ? 'MEMPROSES...'
                      : 'KONFIRMASI &\nPEMBAYARAN',
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
          color: Colors.black.withValues(alpha: 0.15),
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
                    color: Colors.white.withValues(alpha: 0.7),
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
