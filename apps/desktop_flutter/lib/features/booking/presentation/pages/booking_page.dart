import 'package:flutter/material.dart';
import '../../../../app/theme/app_colors.dart';
import '../../../../app/theme/app_text_styles.dart';
import '../../../../shared/layout/sidebar/sidebar.dart';
import '../../../../shared/layout/header/app_header.dart';
import '../../application/booking_controller.dart';
import '../widgets/queue/queue_card.dart';
import '../widgets/package/package_card.dart';
import '../widgets/addon/addon_item.dart';
import '../widgets/summary/order_summary.dart';

class BookingPage extends StatefulWidget {
  const BookingPage({super.key});

  @override
  State<BookingPage> createState() => _BookingPageState();
}

class _BookingPageState extends State<BookingPage> {
  int _selectedNavIndex = 0;
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
          // ── Main Content ──────────────────────────────────────────
          Expanded(
            child: Column(
              children: [
                Expanded(
                  child: Row(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      // Queue sidebar
                      _QueueSidebar(controller: _controller),

                      // Center content
                      Expanded(child: _CenterContent(controller: _controller)),

                      // Order summary
                      Padding(
                        padding: const EdgeInsets.all(16),
                        child: OrderSummaryPanel(controller: _controller),
                      ),
                    ],
                  ),
                ),
              ],
            ),
          ),
        ],
      ),
    );
  }
}

// ══════════════════════════════════════════════════════════════════════════════
// Queue Sidebar (Left panel inside main)
// ══════════════════════════════════════════════════════════════════════════════

class _QueueSidebar extends StatelessWidget {
  final BookingController controller;

  const _QueueSidebar({required this.controller});

  @override
  Widget build(BuildContext context) {
    return Container(
      width: 200,
      decoration: const BoxDecoration(
        color: AppColors.surface,
        border: Border(right: BorderSide(color: AppColors.divider, width: 1)),
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          // Title
          Padding(
            padding: const EdgeInsets.fromLTRB(16, 20, 16, 4),
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text('Booking Search', style: AppTextStyles.h3),
                const SizedBox(height: 2),
                Text(
                  'Kelola antrean pelanggan aktif',
                  style: AppTextStyles.bodySmall,
                ),
                const SizedBox(height: 14),
                // Search
                Container(
                  padding: const EdgeInsets.symmetric(
                    horizontal: 10,
                    vertical: 8,
                  ),
                  decoration: BoxDecoration(
                    color: AppColors.inputBg,
                    borderRadius: BorderRadius.circular(10),
                    border: Border.all(color: AppColors.inputBorder),
                  ),
                  child: Row(
                    children: [
                      Expanded(
                        child: Text(
                          'Cari Kode Booking/\nWhatsApp',
                          style: AppTextStyles.bodySmall.copyWith(fontSize: 11),
                        ),
                      ),
                      const Icon(
                        Icons.search_rounded,
                        size: 16,
                        color: AppColors.textMuted,
                      ),
                    ],
                  ),
                ),
                const SizedBox(height: 14),
              ],
            ),
          ),

          // Queue list
          Expanded(
            child: ListView.builder(
              padding: const EdgeInsets.symmetric(horizontal: 12),
              itemCount: controller.queues.length,
              itemBuilder: (context, index) => QueueCard(
                booking: controller.queues[index],
                isActive: controller.selectedQueueIndex == index,
                onTap: () => controller.selectQueue(index),
              ),
            ),
          ),

          // Pagination
          Padding(
            padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 12),
            child: Row(
              mainAxisAlignment: MainAxisAlignment.spaceBetween,
              children: [
                _PaginationBtn(label: '< Prev', onTap: () {}),
                Row(
                  children: [
                    _PaginationDot(isActive: true),
                    const SizedBox(width: 4),
                    _PaginationDot(isActive: false),
                    const SizedBox(width: 4),
                    _PaginationDot(isActive: false),
                  ],
                ),
                _PaginationBtn(label: 'Next >', onTap: () {}),
              ],
            ),
          ),
        ],
      ),
    );
  }
}

class _PaginationBtn extends StatelessWidget {
  final String label;
  final VoidCallback onTap;

  const _PaginationBtn({required this.label, required this.onTap});

  @override
  Widget build(BuildContext context) {
    return GestureDetector(
      onTap: onTap,
      child: Text(
        label,
        style: AppTextStyles.bodySmall.copyWith(fontWeight: FontWeight.w600),
      ),
    );
  }
}

class _PaginationDot extends StatelessWidget {
  final bool isActive;

  const _PaginationDot({required this.isActive});

  @override
  Widget build(BuildContext context) {
    return Container(
      width: isActive ? 16 : 7,
      height: 7,
      decoration: BoxDecoration(
        color: isActive ? AppColors.primary : AppColors.cardBorder,
        borderRadius: BorderRadius.circular(4),
      ),
    );
  }
}

// ══════════════════════════════════════════════════════════════════════════════
// Center Content
// ══════════════════════════════════════════════════════════════════════════════

class _CenterContent extends StatelessWidget {
  final BookingController controller;

  const _CenterContent({required this.controller});

  @override
  Widget build(BuildContext context) {
    return SingleChildScrollView(
      padding: const EdgeInsets.all(20),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          // Customer info row
          _CustomerInfoRow(controller: controller),
          const SizedBox(height: 24),

          // Pilih Paket
          Text('Pilih Paket', style: AppTextStyles.h3),
          const SizedBox(height: 14),
          _PackageSection(controller: controller),
          const SizedBox(height: 24),

          // Add-ons
          Text('Experience Add-ons', style: AppTextStyles.h3),
          const SizedBox(height: 12),
          _AddonSection(controller: controller),
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
        _InfoField(label: 'NAMA PELANGGAN', value: controller.customerName),
        const SizedBox(width: 12),
        _InfoField(label: 'WHATSAPP (OPSIONAL)', value: controller.whatsapp),
        const SizedBox(width: 12),
        _InfoField(
          label: 'JUMLAH ORANG',
          value: controller.jumlahOrang.toString(),
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
    return SizedBox(
      height: 185,
      child: ListView.separated(
        scrollDirection: Axis.horizontal,
        itemCount: controller.packages.length,
        separatorBuilder: (_, __) => const SizedBox(width: 12),
        itemBuilder: (context, index) => PackageCard(
          package: controller.packages[index],
          isSelected: controller.selectedPackageIndex == index,
          onTap: () => controller.selectPackage(index),
        ),
      ),
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
