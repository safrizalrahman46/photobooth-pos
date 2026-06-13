import 'package:flutter/material.dart';
import '../../../../app/theme/app_colors.dart';
import '../../../../app/theme/app_text_styles.dart';
import '../../application/booking_controller.dart';
import '../../domain/entities/booking.dart';
import '../widgets/dialogs/booking_detail_dialog.dart';

class PureBookingPage extends StatefulWidget {
  const PureBookingPage({super.key});

  @override
  State<PureBookingPage> createState() => _PureBookingPageState();
}

class _PureBookingPageState extends State<PureBookingPage> {
  late BookingController _controller;
  final TextEditingController _searchController = TextEditingController();

  @override
  void initState() {
    super.initState();
    _controller = BookingController();
    _controller.addListener(() {
      if (mounted) setState(() {});
    });
  }

  @override
  void dispose() {
    _controller.dispose();
    _searchController.dispose();
    super.dispose();
  }

  void _showDetailDialog(BuildContext context, BookingController controller, int index) {
    if (index < 0 || index >= controller.queues.length) return;

    controller.selectQueue(index);
    showDialog(
      context: context,
      useSafeArea: false,
      builder: (_) => BookingDetailDialog(controller: controller),
    );
  }

  @override
  Widget build(BuildContext context) {
    final search = _searchController.text.toLowerCase().trim();
    final filteredQueues = _controller.queues.where((booking) {
      if (search.isEmpty) return true;
      return booking.id.toLowerCase().contains(search) ||
          booking.customerName.toLowerCase().contains(search) ||
          booking.phone.toLowerCase().contains(search);
    }).toList();

    return Scaffold(
      backgroundColor: AppColors.background,
      body: Padding(
        padding: const EdgeInsets.all(32),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            // Premium Solid Blue Header Card
            Container(
              width: double.infinity,
              padding: const EdgeInsets.all(28),
              decoration: BoxDecoration(
                color: AppColors.primary,
                borderRadius: BorderRadius.circular(24),
                boxShadow: [
                  BoxShadow(
                    color: AppColors.primary.withValues(alpha: 0.15),
                    blurRadius: 16,
                    offset: const Offset(0, 8),
                  ),
                ],
              ),
              child: Row(
                children: [
                  Container(
                    padding: const EdgeInsets.all(16),
                    decoration: BoxDecoration(
                      color: Colors.white.withValues(alpha: 0.12),
                      borderRadius: BorderRadius.circular(16),
                      border: Border.all(color: Colors.white.withValues(alpha: 0.2)),
                    ),
                    child: const Icon(Icons.bookmark_added_rounded, color: Colors.white, size: 36),
                  ),
                  const SizedBox(width: 20),
                  const Expanded(
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        Text(
                          'Daftar Booking',
                          style: TextStyle(
                            fontSize: 26,
                            fontWeight: FontWeight.w900,
                            color: Colors.white,
                            letterSpacing: 0.5,
                          ),
                        ),
                        SizedBox(height: 6),
                        Text(
                          'Verifikasi booking; booking hari ini otomatis masuk antrean.',
                          style: TextStyle(color: Colors.white70, fontSize: 13, height: 1.4),
                        ),
                      ],
                    ),
                  ),
                  const SizedBox(width: 20),
                  Material(
                    color: Colors.white.withValues(alpha: 0.15),
                    borderRadius: BorderRadius.circular(16),
                    child: InkWell(
                      onTap: _controller.isLoading ? null : () => _controller.loadInitialData(),
                      borderRadius: BorderRadius.circular(16),
                      child: Container(
                        padding: const EdgeInsets.symmetric(horizontal: 20, vertical: 16),
                        decoration: BoxDecoration(
                          borderRadius: BorderRadius.circular(16),
                          border: Border.all(color: Colors.white.withValues(alpha: 0.15)),
                        ),
                        child: Row(
                          mainAxisSize: MainAxisSize.min,
                          children: [
                            if (_controller.isLoading)
                              const SizedBox(
                                width: 16,
                                height: 16,
                                child: CircularProgressIndicator(
                                  strokeWidth: 2,
                                  valueColor: AlwaysStoppedAnimation<Color>(Colors.white),
                                ),
                              )
                            else
                              const Icon(Icons.refresh_rounded, color: Colors.white, size: 18),
                            const SizedBox(width: 8),
                            const Text(
                              'Refresh',
                              style: TextStyle(color: Colors.white, fontWeight: FontWeight.bold, fontSize: 13),
                            ),
                          ],
                        ),
                      ),
                    ),
                  ),
                ],
              ),
            ),
            const SizedBox(height: 24),

            // Search Bar Section
            Row(
              children: [
                Expanded(
                  child: Container(
                    decoration: BoxDecoration(
                      color: Colors.white,
                      borderRadius: BorderRadius.circular(18),
                      boxShadow: [
                        BoxShadow(
                          color: Colors.black.withValues(alpha: 0.02),
                          blurRadius: 10,
                          offset: const Offset(0, 4),
                        ),
                      ],
                    ),
                    child: TextField(
                      controller: _searchController,
                      style: const TextStyle(fontWeight: FontWeight.w600, fontSize: 14),
                      onChanged: (_) => setState(() {}),
                      decoration: InputDecoration(
                        hintText: 'Cari berdasarkan ID booking, nama customer, atau nomor HP...',
                        hintStyle: const TextStyle(color: Colors.grey, fontWeight: FontWeight.normal),
                        prefixIcon: const Icon(Icons.search_rounded, color: Color(0xFF64748B)),
                        suffixIcon: _searchController.text.isNotEmpty
                            ? IconButton(
                                icon: const Icon(Icons.close_rounded, size: 20),
                                onPressed: () {
                                  _searchController.clear();
                                  setState(() {});
                                },
                              )
                            : null,
                        filled: true,
                        fillColor: Colors.transparent,
                        contentPadding: const EdgeInsets.symmetric(horizontal: 20, vertical: 18),
                        border: OutlineInputBorder(
                          borderRadius: BorderRadius.circular(18),
                          borderSide: BorderSide.none,
                        ),
                        enabledBorder: OutlineInputBorder(
                          borderRadius: BorderRadius.circular(18),
                          borderSide: BorderSide.none,
                        ),
                        focusedBorder: OutlineInputBorder(
                          borderRadius: BorderRadius.circular(18),
                          borderSide: const BorderSide(color: AppColors.primary, width: 1.5),
                        ),
                      ),
                    ),
                  ),
                ),
              ],
            ),

            if (_controller.errorMessage != null) ...[
              const SizedBox(height: 12),
              Text(
                _controller.errorMessage!,
                style: const TextStyle(color: Colors.redAccent),
              ),
            ],
            const SizedBox(height: 24),
            Expanded(
              child: _controller.isLoading
                  ? const Center(child: CircularProgressIndicator())
                  : _BookingTable(
                      bookings: filteredQueues,
                      controller: _controller,
                      onDetail: _showDetailDialog,
                    ),
            ),
          ],
        ),
      ),
    );
  }
}

class _BookingTable extends StatelessWidget {
  final List<Booking> bookings;
  final BookingController controller;
  final void Function(BuildContext, BookingController, int) onDetail;

  const _BookingTable({
    required this.bookings,
    required this.controller,
    required this.onDetail,
  });

  @override
  Widget build(BuildContext context) {
    return Container(
      decoration: BoxDecoration(
        color: AppColors.surface,
        borderRadius: BorderRadius.circular(16),
        border: Border.all(color: AppColors.cardBorder),
        boxShadow: [
          BoxShadow(
            color: Colors.black.withValues(alpha: 0.03),
            blurRadius: 10,
            offset: const Offset(0, 4),
          ),
        ],
      ),
      child: Column(
        children: [
          _buildHeader(),
          Expanded(
            child: bookings.isEmpty
                ? const Center(
                    child: Padding(
                      padding: EdgeInsets.all(32),
                      child: Text(
                        'Tidak ada booking ditemukan',
                        style: AppTextStyles.bodyMedium,
                      ),
                    ),
                  )
                : ListView.separated(
                    itemCount: bookings.length,
                    separatorBuilder: (_, __) =>
                        const Divider(height: 1, color: AppColors.divider),
                    itemBuilder: (context, index) {
                      final booking = bookings[index];
                      final actualIndex = controller.queues.indexOf(booking);
                      return _BookingRow(
                        booking: booking,
                        onDetail: () => onDetail(context, controller, actualIndex),
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
          _ColHeader(label: 'NOMOR TELEPON', flex: 3),
          _ColHeader(label: 'TOTAL', flex: 2),
          _ColHeader(label: 'TIPE BAYAR', flex: 2),
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
  final VoidCallback onDetail;

  const _BookingRow({
    required this.booking,
    required this.onDetail,
  });

  @override
  Widget build(BuildContext context) {
    return Padding(
      padding: const EdgeInsets.symmetric(horizontal: 24, vertical: 16),
      child: Row(
        children: [
          Expanded(
            flex: 2,
            child: Text(
              booking.id,
              style: AppTextStyles.bodyMedium.copyWith(
                fontWeight: FontWeight.w600,
              ),
            ),
          ),
          Expanded(
            flex: 2,
            child: Text(booking.time, style: AppTextStyles.bodySmall),
          ),
          Expanded(
            flex: 3,
            child: Text(booking.customerName, style: AppTextStyles.bodyMedium),
          ),
          Expanded(
            flex: 3,
            child: Text(booking.phone, style: AppTextStyles.bodySmall),
          ),
          Expanded(
            flex: 2,
            child: Text(
              _formatPrice(booking.totalAmount),
              style: AppTextStyles.bodyMedium.copyWith(
                fontWeight: FontWeight.w700,
              ),
            ),
          ),
          Expanded(
            flex: 2,
            child: Align(
              alignment: Alignment.centerLeft,
              child: Container(
                padding: const EdgeInsets.symmetric(
                  horizontal: 10,
                  vertical: 6,
                ),
                decoration: BoxDecoration(
                  color: booking.paymentType == 'dp50'
                      ? const Color(0xFFFFF7ED)
                      : const Color(0xFFF0FDF4),
                  borderRadius: BorderRadius.circular(6),
                  border: Border.all(
                    color: booking.paymentType == 'dp50'
                        ? const Color(0xFFF97316).withValues(alpha: 0.3)
                        : const Color(0xFF22C55E).withValues(alpha: 0.3),
                  ),
                ),
                child: Text(
                  booking.paymentType == 'dp50' ? 'DP 50%' : 'FULL LUNAS',
                  style: AppTextStyles.caption.copyWith(
                    color: booking.paymentType == 'dp50'
                        ? const Color(0xFFF97316)
                        : const Color(0xFF16A34A),
                    fontWeight: FontWeight.w800,
                    fontSize: 10,
                    letterSpacing: 0.5,
                  ),
                ),
              ),
            ),
          ),
          Expanded(
            flex: 2,
            child: Align(
              alignment: Alignment.centerLeft,
              child: Container(
                padding: const EdgeInsets.symmetric(
                  horizontal: 10,
                  vertical: 6,
                ),
                decoration: BoxDecoration(
                  color: AppColors.primaryLight,
                  borderRadius: BorderRadius.circular(6),
                ),
                child: Text(
                  _bookingStatusLabel(booking.status),
                  style: AppTextStyles.caption.copyWith(
                    color: AppColors.primary,
                    fontWeight: FontWeight.w800,
                    fontSize: 10,
                    letterSpacing: 0.5,
                  ),
                ),
              ),
            ),
          ),
          const SizedBox(width: 40), // GAP ANTARA STATUS DAN AKSI
          Expanded(
            flex: 3,
            child: Row(
              children: [
                _MiniActionBtn(
                  icon: Icons.search_rounded,
                  color: AppColors.primary,
                  onTap: onDetail,
                  label: 'Detail',
                ),
              ],
            ),
          ),
        ],
      ),
    );
  }
}

String _formatPrice(double price) {
  final int p = price.toInt();
  return 'Rp ${p.toString().replaceAllMapped(RegExp(r'\B(?=(\d{3})+(?!\d))'), (m) => '.')}';
}

String _bookingStatusLabel(String status) {
  return switch (status.toLowerCase()) {
    'pending' => 'MENUNGGU',
    'confirmed' => 'TERKONFIRMASI',
    'paid' => 'LUNAS',
    'checked_in' => 'HADIR',
    'in_queue' => 'DALAM ANTREAN',
    'in_session' => 'SESI BERJALAN',
    'done' => 'SELESAI',
    'cancelled' => 'BATAL',
    _ => status.toUpperCase(),
  };
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
            color: color.withValues(alpha: 0.1),
            borderRadius: BorderRadius.circular(8),
          ),
          child: Icon(icon, color: color, size: 18),
        ),
      ),
    );
  }
}
