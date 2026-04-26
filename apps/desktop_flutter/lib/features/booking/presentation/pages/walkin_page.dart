import 'package:flutter/material.dart';
import '../../../../app/theme/app_colors.dart';
import '../../../../app/theme/app_text_styles.dart';
import '../../application/booking_controller.dart';
import '../widgets/package/package_card.dart';
import '../widgets/addon/addon_item.dart';
import '../widgets/summary/order_summary.dart';

class WalkinPage extends StatefulWidget {
  const WalkinPage({super.key});

  @override
  State<WalkinPage> createState() => _WalkinPageState();
}

class _WalkinPageState extends State<WalkinPage> {
  late BookingController _controller;

  @override
  void initState() {
    super.initState();
    _controller = BookingController();
    _controller.addListener(() => setState(() {}));
  }

  @override
  void dispose() {
    _controller.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: AppColors.background,
      body: Row(
        children: [
          // ── LEFT: Main Content (Scrollable & Centered Box) ──────────
          Expanded(
            child: SingleChildScrollView(
              padding: const EdgeInsets.symmetric(horizontal: 40, vertical: 24),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Text('Walk-in Baru', style: AppTextStyles.h2),
                  const SizedBox(height: 24),
                  
                  // Customer info row (Stretched)
                  _CustomerInfoRow(controller: _controller),
                  const SizedBox(height: 32),

                  // Pilih Paket
                  Text('Pilih Paket', style: AppTextStyles.h3),
                  const SizedBox(height: 16),
                  _PackageSection(controller: _controller),
                  const SizedBox(height: 32),

                  // Add-ons
                  Text('Experience Add-ons', style: AppTextStyles.h3),
                  const SizedBox(height: 16),
                  _AddonSection(controller: _controller),
                ],
              ),
            ),
          ),

          // ── RIGHT: Order Summary (Fixed) ────────────────────────────
          Container(
            width: 320,
            padding: const EdgeInsets.all(16),
            child: OrderSummaryPanel(controller: _controller),
          ),
        ],
      ),
    );
  }
}

class _CustomerInfoRow extends StatelessWidget {
  final BookingController controller;

  const _CustomerInfoRow({required this.controller});

  @override
  Widget build(BuildContext context) {
    return Row(
      children: [
        Expanded(
          child: _InfoField(label: 'NAMA PELANGGAN', value: controller.customerName),
        ),
        const SizedBox(width: 16),
        Expanded(
          child: _InfoField(label: 'WHATSAPP (OPSIONAL)', value: controller.whatsapp),
        ),
        const SizedBox(width: 16),
        SizedBox(
          width: 120,
          child: _InfoField(
            label: 'JUMLAH ORANG',
            value: controller.jumlahOrang.toString(),
          ),
        ),
      ],
    );
  }
}

class _InfoField extends StatelessWidget {
  final String label;
  final String value;

  const _InfoField({required this.label, required this.value});

  @override
  Widget build(BuildContext context) {
    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Text(
          label,
          style: AppTextStyles.caption.copyWith(
            fontWeight: FontWeight.w600,
            letterSpacing: 0.5,
          ),
        ),
        const SizedBox(height: 4),
        Container(
          width: double.infinity,
          padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 9),
          decoration: BoxDecoration(
            color: AppColors.surface,
            borderRadius: BorderRadius.circular(10),
            border: Border.all(color: AppColors.cardBorder),
          ),
          child: Text(value, style: AppTextStyles.bodyMedium),
        ),
      ],
    );
  }
}

class _PackageSection extends StatelessWidget {
  final BookingController controller;

  const _PackageSection({required this.controller});

  @override
  Widget build(BuildContext context) {
    return Row(
      children: List.generate(controller.packages.length, (index) {
        return Expanded(
          child: Padding(
            padding: EdgeInsets.only(
              right: index == controller.packages.length - 1 ? 0 : 16,
            ),
            child: PackageCard(
              package: controller.packages[index],
              isSelected: controller.selectedPackageIndex == index,
              onTap: () => controller.selectPackage(index),
            ),
          ),
        );
      }),
    );
  }
}

class _AddonSection extends StatelessWidget {
  final BookingController controller;

  const _AddonSection({required this.controller});

  @override
  Widget build(BuildContext context) {
    return Column(
      children: List.generate(
        controller.addons.length,
        (index) => Padding(
          padding: const EdgeInsets.only(bottom: 10),
          child: AddonItem(
            addon: controller.addons[index],
            index: index,
            onIncrement: () => controller.incrementAddon(index),
            onDecrement: () => controller.decrementAddon(index),
          ),
        ),
      ),
    );
  }
}
