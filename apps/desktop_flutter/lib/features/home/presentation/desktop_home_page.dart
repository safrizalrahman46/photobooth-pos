import 'package:flutter/material.dart';

import 'package:desktop_flutter/shared/models/desktop_session.dart';
import '../../../shared/layout/sidebar/sidebar.dart';
import 'widgets/dialogs/logout_dialog.dart';
import 'widgets/dialogs/session_active_warning_dialog.dart';
import 'package:desktop_flutter/shared/widgets/session_guard.dart';
import 'package:desktop_flutter/core/session/api_session.dart';

// Pages
import '../../booking/presentation/pages/walkin_page.dart';
import '../../booking/presentation/pages/qr_walkin_page.dart';
import '../../booking/presentation/pages/pure_booking_page.dart';
import '../../history/presentation/pages/history_page.dart';
import '../../laporan/presentation/pages/laporan_page.dart';
import '../../addon/presentation/pages/addon_page.dart';
import '../../antrian/presentation/pages/antrian_page.dart';
import '../../stock/presentation/pages/stock_page.dart';
import '../../kasir/presentation/pages/cashier_session_page.dart';

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

  @override
  void initState() {
    super.initState();

    if (widget.session.user.can('transaction.manage')) {
      _selectedPageId = _DesktopPageIds.cashierSession;
    }
  }

  List<_DesktopDestination> _buildDestinations() {
    final user = widget.session.user;

    return [
      if (user.can('transaction.manage'))
        const _DesktopDestination(
          id: _DesktopPageIds.cashierSession,
          label: 'Sesi Kasir',
          icon: Icons.point_of_sale_rounded,
          builder: CashierSessionPage.new,
        ),
      _DesktopDestination(
        id: _DesktopPageIds.walkIn,
        label: 'Walk-in',
        icon: Icons.calendar_today_rounded,
        builder: () => SessionGuard(
          builder: () => const WalkinPage(),
          onNavigateToSession: _navigateToSession,
        ),
      ),
      if (user.can('transaction.manage') && user.can('queue.manage'))
        _DesktopDestination(
          id: _DesktopPageIds.qrWalkIn,
          label: 'QR Walk-in',
          icon: Icons.qr_code_2_rounded,
          builder: () => SessionGuard(
            builder: () => const QrWalkinPage(),
            onNavigateToSession: _navigateToSession,
          ),
        ),
      _DesktopDestination(
        id: _DesktopPageIds.booking,
        label: 'Booking',
        icon: Icons.book_online_rounded,
        builder: () => SessionGuard(
          builder: () => const PureBookingPage(),
          onNavigateToSession: _navigateToSession,
        ),
      ),
      if (user.can('queue.view'))
        _DesktopDestination(
          id: _DesktopPageIds.queue,
          label: 'Antrean',
          icon: Icons.people_alt_rounded,
          builder: () => SessionGuard(
            builder: () => const AntrianPage(),
            onNavigateToSession: _navigateToSession,
          ),
        ),
      _DesktopDestination(
        id: _DesktopPageIds.history,
        label: 'Riwayat',
        icon: Icons.history_rounded,
        builder: () => SessionGuard(
          builder: () => const HistoryPage(),
          onNavigateToSession: _navigateToSession,
        ),
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
          label: 'Stok',
          icon: Icons.inventory_2_outlined,
          builder: StockPage.new,
        ),
    ];
  }

  Future<void> _confirmLogout() async {
    if (_loggingOut || widget.onLogout == null) {
      return;
    }

    final client = ApiSession.client;
    if (client != null) {
      try {
        final session = await client.fetchCurrentCashierSession();
        if (session != null && session.isOpen) {
          if (!mounted) return;
          await showDialog(
            context: context,
            builder: (_) => const SessionActiveWarningDialog(),
          );
          return;
        }
      } catch (_) {
        // If session check fails, allow logout flow to continue
      }
    }

    if (!mounted) return;

    final confirmed = await showDialog<bool>(
      context: context,
      builder: (context) => const LogoutDialog(),
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

  void _navigateToSession() {
    SessionGuard.invalidateCache();
    setState(() {
      _selectedPageId = _DesktopPageIds.cashierSession;
    });
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
            child: activeDestination.builder(),
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
  static const cashierSession = 'cashier-session';
  static const walkIn = 'walk-in';
  static const qrWalkIn = 'qr-walk-in';
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
