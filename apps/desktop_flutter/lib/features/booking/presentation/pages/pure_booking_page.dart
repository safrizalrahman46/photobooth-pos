import 'package:flutter/material.dart';
import '../../../../app/theme/app_colors.dart';
import '../../../../app/theme/app_text_styles.dart';
import '../../application/booking_controller.dart';
import '../widgets/queue/queue_card.dart';
import '../widgets/package/package_card.dart';
import '../widgets/addon/addon_item.dart';
import '../widgets/summary/order_summary.dart';

class PureBookingPage extends StatefulWidget {
  const PureBookingPage({super.key});

  @override
  State<PureBookingPage> createState() => _PureBookingPageState();
}

class _PureBookingPageState extends State<PureBookingPage> {
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
      body: Padding(
        padding: const EdgeInsets.all(32),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Row(
              mainAxisAlignment: MainAxisAlignment.spaceBetween,
              children: [
                Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Text('Daftar Booking', style: AppTextStyles.h2),
                    const SizedBox(height: 4),
                    Text('Kelola pesanan online dan konfirmasi antrean', style: AppTextStyles.bodySmall),
                  ],
                ),
                // Search bar
                Container(
                  width: 300,
                  padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 10),
                  decoration: BoxDecoration(
                    color: AppColors.surface,
                    borderRadius: BorderRadius.circular(12),
                    border: Border.all(color: AppColors.cardBorder),
                  ),
                  child: Row(
                    children: [
                      const Icon(Icons.search, size: 20, color: AppColors.textMuted),
                      const SizedBox(width: 12),
                      Expanded(
                        child: Text('Cari Booking...', style: AppTextStyles.bodySmall),
                      ),
                    ],
                  ),
                ),
              ],
            ),
            const SizedBox(height: 24),
            Expanded(
              child: _BookingTable(controller: _controller),
            ),
          ],
        ),
      ),
    );
  }
}

class _BookingTable extends StatelessWidget {
  final BookingController controller;

  const _BookingTable({required this.controller});

  @override
  Widget build(BuildContext context) {
    return Container(
      decoration: BoxDecoration(
        color: AppColors.surface,
        borderRadius: BorderRadius.circular(16),
        border: Border.all(color: AppColors.cardBorder),
        boxShadow: [
          BoxShadow(
            color: Colors.black.withOpacity(0.03),
            blurRadius: 10,
            offset: const Offset(0, 4),
          ),
        ],
      ),
      child: Column(
        children: [
          _buildHeader(),
          Expanded(
            child: ListView.separated(
              itemCount: controller.queues.length,
              separatorBuilder: (_, __) => const Divider(height: 1, color: AppColors.divider),
              itemBuilder: (context, index) {
                return _BookingRow(
                  booking: controller.queues[index],
                  onAcc: () => controller.accBooking(),
                  onCancel: () => controller.cancelBooking(),
                  onDelete: () => controller.deleteBooking(),
                );
              },
            ),
          ),
        ],
      ),
    );
  }

  Widget _buildHeader() {
    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 24, vertical: 16),
      decoration: const BoxDecoration(
        color: Color(0xFFF9FAFB),
        borderRadius: BorderRadius.only(
          topLeft: Radius.circular(16),
          topRight: Radius.circular(16),
        ),
      ),
      child: const Row(
        children: [
          _ColHeader(label: 'ID BOOKING', flex: 2),
          _ColHeader(label: 'WAKTU', flex: 2),
          _ColHeader(label: 'NAMA PELANGGAN', flex: 3),
          _ColHeader(label: 'WHATSAPP', flex: 3),
          _ColHeader(label: 'TOTAL', flex: 2),
          _ColHeader(label: 'STATUS', flex: 2),
          _ColHeader(label: 'AKSI', flex: 3),
        ],
      ),
    );
  }
}

class _ColHeader extends StatelessWidget {
  final String label;
  final int flex;

  const _ColHeader({required this.label, required this.flex});

  @override
  Widget build(BuildContext context) {
    return Expanded(
      flex: flex,
      child: Text(
        label,
        style: AppTextStyles.caption.copyWith(
          fontWeight: FontWeight.w700,
          color: AppColors.textMuted,
          letterSpacing: 0.5,
        ),
      ),
    );
  }
}

class _BookingRow extends StatelessWidget {
  final dynamic booking;
  final VoidCallback onAcc;
  final VoidCallback onCancel;
  final VoidCallback onDelete;

  const _BookingRow({
    required this.booking,
    required this.onAcc,
    required this.onCancel,
    required this.onDelete,
  });

  @override
  Widget build(BuildContext context) {
    return Padding(
      padding: const EdgeInsets.symmetric(horizontal: 24, vertical: 16),
      child: Row(
        children: [
          Expanded(flex: 2, child: Text(booking.id, style: AppTextStyles.bodyMedium.copyWith(fontWeight: FontWeight.w600))),
          Expanded(flex: 2, child: Text(booking.time, style: AppTextStyles.bodySmall)),
          Expanded(flex: 3, child: Text(booking.customerName, style: AppTextStyles.bodyMedium)),
          Expanded(flex: 3, child: Text(booking.phone, style: AppTextStyles.bodySmall)),
          Expanded(flex: 2, child: Text('Rp 115.000', style: AppTextStyles.bodyMedium.copyWith(fontWeight: FontWeight.w700))),
          Expanded(
            flex: 2,
            child: Container(
              padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 4),
              decoration: BoxDecoration(
                color: AppColors.primaryLight,
                borderRadius: BorderRadius.circular(6),
              ),
              child: UnconstrainedBox(
                child: Text(
                  booking.status,
                  style: AppTextStyles.caption.copyWith(color: AppColors.primary, fontWeight: FontWeight.bold),
                ),
              ),
            ),
          ),
          Expanded(
            flex: 3,
            child: Row(
              children: [
                _MiniActionBtn(icon: Icons.check_circle_rounded, color: const Color(0xFF10B981), onTap: onAcc, label: 'Acc'),
                const SizedBox(width: 8),
                _MiniActionBtn(icon: Icons.cancel_rounded, color: const Color(0xFFF59E0B), onTap: onCancel, label: 'Batal'),
                const SizedBox(width: 8),
                _MiniActionBtn(icon: Icons.delete_rounded, color: const Color(0xFFEF4444), onTap: onDelete, label: 'Hapus'),
              ],
            ),
          ),
        ],
      ),
    );
  }
}

class _MiniActionBtn extends StatelessWidget {
  final IconData icon;
  final Color color;
  final VoidCallback onTap;
  final String label;

  const _MiniActionBtn({
    required this.icon,
    required this.color,
    required this.onTap,
    required this.label,
  });

  @override
  Widget build(BuildContext context) {
    return Tooltip(
      message: label,
      child: InkWell(
        onTap: onTap,
        borderRadius: BorderRadius.circular(8),
        child: Container(
          padding: const EdgeInsets.all(8),
          decoration: BoxDecoration(
            color: color.withOpacity(0.1),
            borderRadius: BorderRadius.circular(8),
          ),
          child: Icon(icon, color: color, size: 18),
        ),
      ),
    );
  }
}
