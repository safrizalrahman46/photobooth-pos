// lib/presentation/pages/main_shell.dart

import 'package:flutter/material.dart';
import '../widgets/sidebar_widget.dart';
import 'addon_page.dart';
import 'daftar_paket_page.dart';

class MainShell extends StatefulWidget {
  const MainShell({super.key});

  @override
  State<MainShell> createState() => _MainShellState();
}

class _MainShellState extends State<MainShell> {
  int _selectedIndex = 4; // Paket is index 4

  final List<Widget> _pages = [
    const _PlaceholderPage(title: 'Booking'),
    const _PlaceholderPage(title: 'Antrean'),
    const _PlaceholderPage(title: 'History'),
    const _PlaceholderPage(title: 'Laporan'),
    const DaftarPaketPage(),
    const AddOnPage(),
  ];

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      body: Row(
        children: [
          // Sidebar
          SidebarWidget(
            selectedIndex: _selectedIndex,
            onItemSelected: (i) => setState(() => _selectedIndex = i),
          ),

          // Vertical divider
          Container(width: 1, color: const Color(0xFFEEF1F7)),

          // Page content
          Expanded(
            child: AnimatedSwitcher(
              duration: const Duration(milliseconds: 200),
              child: KeyedSubtree(
                key: ValueKey(_selectedIndex),
                child: _pages[_selectedIndex],
              ),
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
      child: Text(
        title,
        style: const TextStyle(
          fontSize: 18,
          color: Color(0xFF9DACC2),
        ),
      ),
    );
  }
}
