import 'package:flutter/material.dart';
import '../../../app/theme/app_colors.dart';
import '../../../app/theme/app_text_styles.dart';

class Sidebar extends StatelessWidget {
  final int selectedIndex;
  final ValueChanged<int> onItemTapped;

  const Sidebar({
    super.key,
    required this.selectedIndex,
    required this.onItemTapped,
  });

  @override
  Widget build(BuildContext context) {
    return Container(
      width: 200,
      decoration: const BoxDecoration(
        color: AppColors.sidebarBg,
        border: Border(right: BorderSide(color: AppColors.divider, width: 1)),
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          // Logo
          Padding(
            padding: const EdgeInsets.fromLTRB(20, 24, 20, 28),
            child: Text('Ready To Pict', style: AppTextStyles.h2),
          ),

          // Nav items
          _SidebarItem(
            icon: Icons.calendar_today_rounded,
            label: 'Booking',
            isActive: selectedIndex == 0,
            onTap: () => onItemTapped(0),
          ),
          _SidebarItem(
            icon: Icons.people_alt_rounded,
            label: 'Antrean',
            isActive: selectedIndex == 1,
            onTap: () => onItemTapped(1),
          ),
          _SidebarItem(
            icon: Icons.history_rounded,
            label: 'History',
            isActive: selectedIndex == 2,
            onTap: () => onItemTapped(2),
          ),
          _SidebarItem(
            icon: Icons.bar_chart_rounded,
            label: 'Laporan',
            isActive: selectedIndex == 3,
            onTap: () => onItemTapped(3),
          ),
          _SidebarItem(
            icon: Icons.inventory_2_rounded,
            label: 'Paket',
            isActive: selectedIndex == 4,
            onTap: () => onItemTapped(4),
          ),
          _SidebarItem(
            icon: Icons.extension_rounded,
            label: 'Add-ons',
            isActive: selectedIndex == 5,
            onTap: () => onItemTapped(5),
          ),

          const Spacer(),

          // Footer user
          const _SidebarFooter(),
        ],
      ),
    );
  }
}

class _SidebarItem extends StatelessWidget {
  final IconData icon;
  final String label;
  final bool isActive;
  final VoidCallback onTap;

  const _SidebarItem({
    required this.icon,
    required this.label,
    required this.isActive,
    required this.onTap,
  });

  @override
  Widget build(BuildContext context) {
    return GestureDetector(
      onTap: onTap,
      child: Container(
        margin: const EdgeInsets.symmetric(horizontal: 12, vertical: 2),
        padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 10),
        decoration: BoxDecoration(
          color: isActive ? AppColors.sidebarActive : Colors.transparent,
          borderRadius: BorderRadius.circular(10),
        ),
        child: Row(
          children: [
            Icon(
              icon,
              size: 18,
              color: isActive
                  ? AppColors.textWhite
                  : AppColors.sidebarInactiveText,
            ),
            const SizedBox(width: 10),
            Text(
              label,
              style: AppTextStyles.bodyMedium.copyWith(
                color: isActive
                    ? AppColors.textWhite
                    : AppColors.sidebarInactiveText,
                fontWeight: isActive ? FontWeight.w600 : FontWeight.w400,
              ),
            ),
          ],
        ),
      ),
    );
  }
}

class _SidebarFooter extends StatelessWidget {
  const _SidebarFooter();

  @override
  Widget build(BuildContext context) {
    return Container(
      padding: const EdgeInsets.all(16),
      child: Row(
        children: [
          CircleAvatar(
            radius: 18,
            backgroundColor: AppColors.primaryLight,
            child: const Icon(
              Icons.person_rounded,
              color: AppColors.primary,
              size: 18,
            ),
          ),
          const SizedBox(width: 10),
          Text('Satria', style: AppTextStyles.bodyMedium),
        ],
      ),
    );
  }
}
