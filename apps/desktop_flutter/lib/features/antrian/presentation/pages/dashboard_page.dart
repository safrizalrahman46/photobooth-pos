// presentation/pages/dashboard_page.dart

import 'package:flutter/material.dart';
import 'package:provider/provider.dart';
import '../../application/bloc/antrian_bloc.dart';
import '../theme/app_theme.dart';
import '../widgets/sidebar_widget.dart';
import 'antrian_page.dart';

class DashboardPage extends StatefulWidget {
  const DashboardPage({super.key});

  @override
  State<DashboardPage> createState() => _DashboardPageState();
}

class _DashboardPageState extends State<DashboardPage> {
  int _selectedIndex = 1; // Antrean is default

  Widget _buildContent() {
    switch (_selectedIndex) {
      case 0:
        return const _PlaceholderPage(title: 'Booking');
      case 1:
        return const AntrianPage();
      case 2:
        return const _PlaceholderPage(title: 'History');
      case 3:
        return const _PlaceholderPage(title: 'Laporan');
      case 4:
        return const _PlaceholderPage(title: 'Paket');
      case 5:
        return const _PlaceholderPage(title: 'Add-ons');
      default:
        return const AntrianPage();
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: AppTheme.background,
      body: Row(
        children: [
          // Sidebar
          SidebarWidget(
            selectedIndex: _selectedIndex,
            onItemSelected: (i) => setState(() => _selectedIndex = i),
          ),

          // Divider
          Container(width: 1, color: AppTheme.divider),

          // Main content
          Expanded(
            child: Padding(
              padding: const EdgeInsets.all(24),
              child: _buildContent(),
            ),
          ),
        ],
      ),
    );
  }
}

class _PlaceholderPage extends StatelessWidget {
  final String title;
  const _PlaceholderPage({required this.title});

  @override
  Widget build(BuildContext context) {
    return Center(
      child: Column(
        mainAxisSize: MainAxisSize.min,
        children: [
          Icon(Icons.construction_outlined, size: 48, color: AppTheme.textSecondary),
          const SizedBox(height: 12),
          Text(
            'Halaman $title',
            style: const TextStyle(
              fontFamily: AppTheme.fontFamily,
              fontSize: 20,
              fontWeight: FontWeight.w600,
              color: AppTheme.textPrimary,
            ),
          ),
          const SizedBox(height: 4),
          const Text(
            'Segera hadir',
            style: TextStyle(
              fontFamily: AppTheme.fontFamily,
              fontSize: 14,
              color: AppTheme.textSecondary,
            ),
          ),
        ],
      ),
    );
  }
}
