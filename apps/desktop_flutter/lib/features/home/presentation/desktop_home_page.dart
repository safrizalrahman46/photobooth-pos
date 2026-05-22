import 'package:flutter/material.dart';
import 'package:provider/provider.dart';

import 'package:desktop_flutter/shared/models/desktop_session.dart';
import '../../../shared/layout/sidebar/sidebar.dart';
import '../../../shared/layout/header/app_header.dart';
import '../../../app/theme/app_colors.dart';
import '../../../app/theme/app_text_styles.dart';

// Pages
import '../../booking/presentation/pages/walkin_page.dart';
import '../../booking/presentation/pages/pure_booking_page.dart';
import '../../history/presentation/pages/history_page.dart';
import '../../laporan/presentation/pages/laporan_page.dart';
import '../../addon/presentation/pages/addon_page.dart';
import '../../antrian/presentation/pages/antrian_page.dart';
import '../../stock/presentation/pages/stock_page.dart';

// Injector
import '../../antrian/antrian_injector.dart';

// ╔═╗╦ ╦╔═╗╔═╗╔═╗  ╦╔═╔═╗╔╗╔╔╦╗╔═╗╦
// ╚═╗║ ║║  ╠═╣╠═╝  ╠╩╗║ ║║║║ ║ ║ ║║
// ╚═╝╚═╝╚═╝╩ ╩╩    ╩ ╩╚═╝╝╚╝ ╩ ╚═╝╩═╝

class DesktopHomePage extends StatefulWidget {
  final DesktopSession session;
  final Future<void> Function()? onLogout;

  const DesktopHomePage({super.key, required this.session, this.onLogout});

  @override
  State<DesktopHomePage> createState() => _DesktopHomePageState();
}

class _DesktopHomePageState extends State<DesktopHomePage> {
  String _selectedPageId = _DesktopPageIds.walkIn;
  bool _isSidebarExpanded = true; // State baru untuk sidebar
  bool _loggingOut = false;

  List<_DesktopDestination> _buildDestinations() {
    final user = widget.session.user;

    return [
      const _DesktopDestination(
        id: _DesktopPageIds.walkIn,
        label: 'Walk-in',
        icon: Icons.calendar_today_rounded,
        builder: WalkinPage.new,
      ),
      const _DesktopDestination(
        id: _DesktopPageIds.booking,
        label: 'Booking',
        icon: Icons.book_online_rounded,
        builder: PureBookingPage.new,
      ),
      _DesktopDestination(
        id: _DesktopPageIds.queue,
        label: 'Antrean',
        icon: Icons.people_alt_rounded,
        builder: () => ChangeNotifierProvider(
          create: (_) => AntrianInjector.create(),
          child: const AntrianPage(),
        ),
      ),
      const _DesktopDestination(
        id: _DesktopPageIds.history,
        label: 'History',
        icon: Icons.history_rounded,
        builder: HistoryPage.new,
      ),
      if (user.can('report.view'))
        const _DesktopDestination(
          id: _DesktopPageIds.reports,
          label: 'Laporan',
          icon: Icons.bar_chart_rounded,
          builder: LaporanPage.new,
        ),
      if (user.can('catalog.manage'))
        const _DesktopDestination(
          id: _DesktopPageIds.addOns,
          label: 'Add-ons',
          icon: Icons.extension_rounded,
          builder: AddOnPage.new,
        ),
      if (user.canViewStock)
        const _DesktopDestination(
          id: _DesktopPageIds.stock,
          label: 'Stock',
          icon: Icons.inventory_2_outlined,
          builder: StockPage.new,
        ),
    ];
  }

  Future<void> _confirmLogout() async {
    if (_loggingOut || widget.onLogout == null) {
      return;
    }

    final confirmed = await showDialog<bool>(
      context: context,
      builder: (context) {
        return AlertDialog(
          backgroundColor: AppColors.surface,
          surfaceTintColor: Colors.transparent,
          shape: RoundedRectangleBorder(
            borderRadius: BorderRadius.circular(20),
            side: const BorderSide(color: AppColors.cardBorder, width: 1),
          ),
          titlePadding: const EdgeInsets.fromLTRB(24, 24, 24, 12),
          contentPadding: const EdgeInsets.symmetric(horizontal: 24, vertical: 8),
          actionsPadding: const EdgeInsets.fromLTRB(24, 16, 24, 24),
          title: Row(
            children: [
              Container(
                padding: const EdgeInsets.all(8),
                decoration: BoxDecoration(
                  color: const Color(0xFFFFF1F1),
                  borderRadius: BorderRadius.circular(10),
                ),
                child: const Icon(
                  Icons.logout_rounded,
                  color: Colors.redAccent,
                  size: 20,
                ),
              ),
              const SizedBox(width: 12),
              Text(
                'Logout',
                style: AppTextStyles.h2.copyWith(color: AppColors.textPrimary),
              ),
            ],
          ),
          content: Text(
            'Yakin ingin keluar dari aplikasi desktop?',
            style: AppTextStyles.bodyMedium.copyWith(color: AppColors.textSecondary),
          ),
          actions: [
            TextButton(
              onPressed: () => Navigator.of(context).pop(false),
              style: TextButton.styleFrom(
                padding: const EdgeInsets.symmetric(horizontal: 20, vertical: 14),
                shape: RoundedRectangleBorder(
                  borderRadius: BorderRadius.circular(12),
                ),
              ),
              child: Text(
                'Batal',
                style: AppTextStyles.bodyMedium.copyWith(
                  color: AppColors.textSecondary,
                  fontWeight: FontWeight.w600,
                ),
              ),
            ),
            const SizedBox(width: 4),
            ElevatedButton(
              onPressed: () => Navigator.of(context).pop(true),
              style: ElevatedButton.styleFrom(
                backgroundColor: Colors.redAccent,
                foregroundColor: Colors.white,
                elevation: 0,
                padding: const EdgeInsets.symmetric(horizontal: 24, vertical: 14),
                shape: RoundedRectangleBorder(
                  borderRadius: BorderRadius.circular(12),
                ),
              ),
              child: Text(
                'Logout',
                style: AppTextStyles.bodyMedium.copyWith(
                  color: Colors.white,
                  fontWeight: FontWeight.w600,
                ),
              ),
            ),
          ],
        );
      },
    );

    if (confirmed != true || !mounted) {
      return;
    }

    setState(() => _loggingOut = true);

    try {
      await widget.onLogout?.call();
    } finally {
      if (mounted) {
        setState(() => _loggingOut = false);
      }
    }
  }

  @override
  Widget build(BuildContext context) {
    final destinations = _buildDestinations();
    final activeDestination = destinations.firstWhere(
      (destination) => destination.id == _selectedPageId,
      orElse: () => destinations.first,
    );
    final roleLabel = _roleLabel(widget.session.user.roles);

    return Scaffold(
      body: Row(
        children: [
          // ── Sidebar ─────────────────────────────
          Sidebar(
            selectedId: activeDestination.id,
            destinations: destinations
                .map((destination) => destination.toSidebarDestination())
                .toList(),
            isExpanded: _isSidebarExpanded, // Pass state
            userName: widget.session.user.name,
            userRoleLabel: roleLabel,
            onLogout: widget.onLogout == null || _loggingOut
                ? null
                : _confirmLogout,
            onToggle: () {
              // Toggle callback
              setState(() {
                _isSidebarExpanded = !_isSidebarExpanded;
              });
            },
            onItemTapped: (pageId) {
              setState(() {
                _selectedPageId = pageId;
              });
            },
          ),

          // ── Content ─────────────────────────────
          Expanded(
            child: Column(
              children: [
                const AppHeader(),

                Expanded(child: activeDestination.builder()),
              ],
            ),
          ),
        ],
      ),
    );
  }

  String _roleLabel(List<String> roles) {
    if (roles.isEmpty) {
      return 'User';
    }

    final role = roles.first;
    if (role.isEmpty) {
      return 'User';
    }

    return '${role[0].toUpperCase()}${role.substring(1)}';
  }
}

class _DesktopPageIds {
  static const walkIn = 'walk-in';
  static const booking = 'booking';
  static const queue = 'queue';
  static const history = 'history';
  static const reports = 'reports';
  static const addOns = 'add-ons';
  static const stock = 'stock';
}

class _DesktopDestination {
  const _DesktopDestination({
    required this.id,
    required this.label,
    required this.icon,
    required this.builder,
  });

  final String id;
  final String label;
  final IconData icon;
  final Widget Function() builder;

  SidebarDestination toSidebarDestination() {
    return SidebarDestination(id: id, icon: icon, label: label);
  }
}

// import 'package:desktop_flutter/features/kasir/presentation/kasir_dashboard_panel.dart';
// import 'package:desktop_flutter/features/owner/presentation/owner_dashboard_panel.dart';
// import 'package:desktop_flutter/shared/models/desktop_session.dart';
// import 'package:flutter/material.dart';

// class DesktopHomePage extends StatefulWidget {
//   const DesktopHomePage({
//     super.key,
//     required this.session,
//     required this.onLogout,
//   });

//   final DesktopSession session;
//   final Future<void> Function() onLogout;

//   @override
//   State<DesktopHomePage> createState() => _DesktopHomePageState();
// }

// class _DesktopHomePageState extends State<DesktopHomePage> {
//   int _selectedIndex = 0;

//   @override
//   Widget build(BuildContext context) {
//     final destinations = <_Destination>[
//       if (widget.session.user.isCashier)
//         const _Destination(
//           icon: Icons.point_of_sale_rounded,
//           label: 'Kasir',
//           builder: KasirDashboardPanel.new,
//         ),
//       if (widget.session.user.isOwner)
//         const _Destination(
//           icon: Icons.analytics_outlined,
//           label: 'Owner',
//           builder: OwnerDashboardPanel.new,
//         ),
//     ];

//     final activeIndex = destinations.isEmpty
//         ? 0
//         : _selectedIndex.clamp(0, destinations.length - 1);

//     return Scaffold(
//       body: Row(
//         children: <Widget>[
//           NavigationRail(
//             selectedIndex: activeIndex,
//             minWidth: 84,
//             labelType: NavigationRailLabelType.all,
//             onDestinationSelected: (int index) {
//               setState(() {
//                 _selectedIndex = index;
//               });
//             },
//             leading: Padding(
//               padding: const EdgeInsets.only(top: 20),
//               child: Column(
//                 children: <Widget>[
//                   const CircleAvatar(
//                     radius: 22,
//                     backgroundColor: Color(0xFFB5672A),
//                     child: Icon(Icons.camera_alt_outlined, color: Colors.white),
//                   ),
//                   const SizedBox(height: 12),
//                   Text(
//                     widget.session.user.name,
//                     textAlign: TextAlign.center,
//                     style: Theme.of(context).textTheme.labelMedium,
//                   ),
//                 ],
//               ),
//             ),
//             trailing: Padding(
//               padding: const EdgeInsets.only(bottom: 24),
//               child: IconButton(
//                 tooltip: 'Logout',
//                 onPressed: widget.onLogout,
//                 icon: const Icon(Icons.logout_rounded),
//               ),
//             ),
//             destinations: destinations
//                 .map(
//                   (item) => NavigationRailDestination(
//                     icon: Icon(item.icon),
//                     label: Text(item.label),
//                   ),
//                 )
//                 .toList(),
//           ),
//           const VerticalDivider(width: 1),
//           Expanded(
//             child: Padding(
//               padding: const EdgeInsets.all(24),
//               child: Column(
//                 crossAxisAlignment: CrossAxisAlignment.start,
//                 children: <Widget>[
//                   Text(
//                     'Connected to ${widget.session.baseUrl}',
//                     style: Theme.of(context).textTheme.labelLarge,
//                   ),
//                   const SizedBox(height: 8),
//                   Text(
//                     'Roles: ${widget.session.user.roles.join(', ')}',
//                     style: Theme.of(context).textTheme.bodyMedium,
//                   ),
//                   const SizedBox(height: 24),
//                   Expanded(
//                     child: destinations.isEmpty
//                         ? const Center(child: Text('Role akun belum dikenali.'))
//                         : destinations[activeIndex].builder(widget.session),
//                   ),
//                 ],
//               ),
//             ),
//           ),
//         ],
//       ),
//     );
//   }
// }

// class _Destination {
//   const _Destination({
//     required this.icon,
//     required this.label,
//     required this.builder,
//   });

//   final IconData icon;
//   final String label;
//   final Widget Function(DesktopSession session) builder;
// }
