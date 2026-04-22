import 'package:flutter/material.dart';
import '../../booking/presentation/pages/booking_page.dart';

class DesktopHomePage extends StatelessWidget {
  final dynamic session;
  final VoidCallback? onLogout;

  const DesktopHomePage({super.key, this.session, this.onLogout});

  @override
  Widget build(BuildContext context) {
    return const Scaffold(
      body: BookingPage(), // 🔥 tetap test booking
    );
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
