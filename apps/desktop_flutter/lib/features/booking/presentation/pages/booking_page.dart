// presentation/pages/booking_page.dart

import 'package:flutter/material.dart';
import 'package:provider/provider.dart';
import '../../application/order_notifier.dart';
import '../widgets/booking_queue_list.dart';
import '../widgets/customer_form.dart';
import '../widgets/package_section.dart';
import '../widgets/addon_section.dart';
import '../widgets/order_summary.dart';

class BookingPage extends StatelessWidget {
  const BookingPage({super.key});

  @override
  Widget build(BuildContext context) {
    return ChangeNotifierProvider(
      create: (_) => OrderNotifier(),
      child: const _BookingPageContent(),
    );
  }
}

class _BookingPageContent extends StatelessWidget {
  const _BookingPageContent();

  @override
  Widget build(BuildContext context) {
    final notifier = context.watch<OrderNotifier>();
    final state = notifier.state;

    return Scaffold(
      backgroundColor: const Color(0xFFF8F9FB),
      body: Row(
        children: [
          // ─── Left sidebar ───────────────────────────
          _Sidebar(),
          // ─── Queue list ─────────────────────────────
          Container(
            width: 190,
            color: Colors.white,
            padding: const EdgeInsets.fromLTRB(16, 24, 16, 16),
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                const Text(
                  'Booking Search',
                  style: TextStyle(
                    fontSize: 16,
                    fontWeight: FontWeight.w800,
                    color: Color(0xFF111827),
                  ),
                ),
                const SizedBox(height: 2),
                const Text(
                  'Kelola antrean pelanggan aktif',
                  style: TextStyle(fontSize: 11, color: Color(0xFF9CA3AF)),
                ),
                const SizedBox(height: 16),
                Expanded(child: BookingQueueList(queues: OrderNotifier.queues)),
              ],
            ),
          ),
          // ─── Main content ────────────────────────────
          Expanded(
            child: SingleChildScrollView(
              padding: const EdgeInsets.all(28),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  // Search bar
                  Container(
                    height: 44,
                    decoration: BoxDecoration(
                      color: Colors.white,
                      border: Border.all(color: const Color(0xFFE5E7EB)),
                      borderRadius: BorderRadius.circular(22),
                    ),
                    child: const Row(
                      children: [
                        SizedBox(width: 14),
                        Icon(Icons.search, size: 18, color: Color(0xFF9CA3AF)),
                        SizedBox(width: 8),
                        Text(
                          'Search product or customer...',
                          style: TextStyle(
                            fontSize: 13,
                            color: Color(0xFF9CA3AF),
                          ),
                        ),
                      ],
                    ),
                  ),
                  const SizedBox(height: 24),
                  // Customer form
                  CustomerForm(
                    customerName: state.customerName,
                    whatsapp: state.whatsapp,
                    jumlahOrang: state.jumlahOrang,
                  ),
                  const SizedBox(height: 28),
                  // Package section
                  PackageSection(
                    packages: OrderNotifier.packages,
                    selectedPackageId: state.selectedPackageId,
                    onPackageSelected: notifier.selectPackage,
                  ),
                  const SizedBox(height: 28),
                  // Add-ons section
                  AddonSection(
                    addOns: state.addOns,
                    onAdd: notifier.incrementAddOn,
                    onIncrement: notifier.incrementAddOn,
                    onDecrement: notifier.decrementAddOn,
                  ),
                ],
              ),
            ),
          ),
          // ─── Order summary panel ─────────────────────
          Padding(
            padding: const EdgeInsets.all(16),
            child: OrderSummaryPanel(
              items: notifier.summaryItems,
              grandTotal: notifier.grandTotal,
              isPaid: state.isPaid,
              paymentMethod: state.paymentMethod,
              onPaymentMethodChanged: notifier.setPaymentMethod,
              onConfirm: () {
                // TODO: handle confirm & print
              },
            ),
          ),
        ],
      ),
    );
  }
}

class _Sidebar extends StatelessWidget {
  const _Sidebar();

  @override
  Widget build(BuildContext context) {
    final items = [
      _SidebarItem(
        icon: Icons.calendar_today,
        label: 'Booking',
        isActive: true,
      ),
      _SidebarItem(icon: Icons.history, label: 'History'),
      _SidebarItem(icon: Icons.bar_chart_outlined, label: 'Laporan'),
      _SidebarItem(icon: Icons.grid_view_outlined, label: 'Paket'),
      _SidebarItem(icon: Icons.extension_outlined, label: 'Add-ons'),
    ];

    return Container(
      width: 180,
      color: Colors.white,
      padding: const EdgeInsets.symmetric(vertical: 24, horizontal: 16),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          // Logo
          const Text(
            'Ready To Pict',
            style: TextStyle(
              fontSize: 16,
              fontWeight: FontWeight.w800,
              color: Color(0xFF111827),
            ),
          ),
          const SizedBox(height: 28),
          // Nav items
          ...items.map((item) => _NavItem(item: item)),
          const Spacer(),
          // User avatar
          Row(
            children: [
              Container(
                width: 36,
                height: 36,
                decoration: const BoxDecoration(
                  color: Color(0xFF1E3A5F),
                  shape: BoxShape.circle,
                ),
                child: const Center(
                  child: Text(
                    'S',
                    style: TextStyle(
                      color: Colors.white,
                      fontWeight: FontWeight.w700,
                      fontSize: 14,
                    ),
                  ),
                ),
              ),
              const SizedBox(width: 10),
              const Text(
                'Satria',
                style: TextStyle(
                  fontSize: 13,
                  fontWeight: FontWeight.w600,
                  color: Color(0xFF374151),
                ),
              ),
            ],
          ),
        ],
      ),
    );
  }
}

class _SidebarItem {
  final IconData icon;
  final String label;
  final bool isActive;

  const _SidebarItem({
    required this.icon,
    required this.label,
    this.isActive = false,
  });
}

class _NavItem extends StatelessWidget {
  final _SidebarItem item;

  const _NavItem({required this.item});

  @override
  Widget build(BuildContext context) {
    return Container(
      margin: const EdgeInsets.only(bottom: 4),
      decoration: BoxDecoration(
        color: item.isActive
            ? const Color(0xFF1E3A5F).withOpacity(0.1)
            : Colors.transparent,
        borderRadius: BorderRadius.circular(8),
      ),
      child: ListTile(
        dense: true,
        contentPadding: const EdgeInsets.symmetric(horizontal: 10, vertical: 0),
        leading: Icon(
          item.icon,
          size: 18,
          color: item.isActive
              ? const Color(0xFF1E3A5F)
              : const Color(0xFF9CA3AF),
        ),
        title: Text(
          item.label,
          style: TextStyle(
            fontSize: 13,
            fontWeight: item.isActive ? FontWeight.w700 : FontWeight.w500,
            color: item.isActive
                ? const Color(0xFF1E3A5F)
                : const Color(0xFF6B7280),
          ),
        ),
        shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(8)),
        onTap: () {},
      ),
    );
  }
}
