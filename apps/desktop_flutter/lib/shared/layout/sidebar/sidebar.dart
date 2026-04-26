import 'package:flutter/material.dart';
import '../../../app/theme/app_colors.dart';
import '../../../app/theme/app_text_styles.dart';

class Sidebar extends StatelessWidget {
  final int selectedIndex;
  final ValueChanged<int> onItemTapped;
  final bool isExpanded;
  final VoidCallback onToggle;

  const Sidebar({
    super.key,
    required this.selectedIndex,
    required this.onItemTapped,
    required this.isExpanded,
    required this.onToggle,
  });

  @override
  Widget build(BuildContext context) {
    return AnimatedContainer(
      duration: const Duration(milliseconds: 250),
      curve: Curves.easeInOut,
      width: isExpanded ? 240 : 88,
      decoration: const BoxDecoration(
        color: AppColors.sidebarBg,
        border: Border(right: BorderSide(color: AppColors.divider, width: 1)),
      ),
      child: ClipRect(
        child: OverflowBox(
          alignment: Alignment.topLeft,
          maxWidth: isExpanded ? 240 : 88,
          minWidth: isExpanded ? 240 : 88,
          child: Column(
            crossAxisAlignment: isExpanded ? CrossAxisAlignment.start : CrossAxisAlignment.center,
            children: [
              // Logo & Toggle Area
              Padding(
                padding: EdgeInsets.fromLTRB(isExpanded ? 20 : 8, 24, isExpanded ? 20 : 8, 32),
                child: Row(
                  mainAxisAlignment: isExpanded ? MainAxisAlignment.spaceBetween : MainAxisAlignment.center,
                  children: [
                    if (isExpanded)
                      Text('Ready To Pict', style: AppTextStyles.h2),
                    
                    IconButton(
                      onPressed: onToggle,
                      icon: Icon(
                        isExpanded ? Icons.menu_open_rounded : Icons.menu_rounded,
                        color: AppColors.textPrimary,
                        size: 24,
                      ),
                      splashRadius: 20,
                      tooltip: isExpanded ? 'Tutup Sidebar' : 'Buka Sidebar',
                    ),
                  ],
                ),
              ),

              // Nav items
              _SidebarItem(
                icon: Icons.calendar_today_rounded,
                label: 'Walk-in',
                isActive: selectedIndex == 0,
                isExpanded: isExpanded,
                onTap: () => onItemTapped(0),
              ),
              _SidebarItem(
                icon: Icons.book_online_rounded,
                label: 'Booking',
                isActive: selectedIndex == 1,
                isExpanded: isExpanded,
                onTap: () => onItemTapped(1),
              ),
              _SidebarItem(
                icon: Icons.people_alt_rounded,
                label: 'Antrean',
                isActive: selectedIndex == 2,
                isExpanded: isExpanded,
                onTap: () => onItemTapped(2),
              ),
              _SidebarItem(
                icon: Icons.history_rounded,
                label: 'History',
                isActive: selectedIndex == 3,
                isExpanded: isExpanded,
                onTap: () => onItemTapped(3),
              ),
              _SidebarItem(
                icon: Icons.bar_chart_rounded,
                label: 'Laporan',
                isActive: selectedIndex == 4,
                isExpanded: isExpanded,
                onTap: () => onItemTapped(4),
              ),
              _SidebarItem(
                icon: Icons.inventory_2_rounded,
                label: 'Paket',
                isActive: selectedIndex == 5,
                isExpanded: isExpanded,
                onTap: () => onItemTapped(5),
              ),
              _SidebarItem(
                icon: Icons.extension_rounded,
                label: 'Add-ons',
                isActive: selectedIndex == 6,
                isExpanded: isExpanded,
                onTap: () => onItemTapped(6),
              ),

              const Spacer(),

              // Footer user
              _SidebarFooter(isExpanded: isExpanded),
            ],
          ),
        ),
      ),
    );
  }
}

class _SidebarItem extends StatelessWidget {
  final IconData icon;
  final String label;
  final bool isActive;
  final bool isExpanded;
  final VoidCallback onTap;

  const _SidebarItem({
    required this.icon,
    required this.label,
    required this.isActive,
    required this.isExpanded,
    required this.onTap,
  });

  @override
  Widget build(BuildContext context) {
    return GestureDetector(
      onTap: onTap,
      child: Tooltip(
        message: isExpanded ? '' : label,
        child: Container(
          margin: const EdgeInsets.symmetric(horizontal: 16, vertical: 4),
          padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 12),
          decoration: BoxDecoration(
            color: isActive ? AppColors.sidebarActive : Colors.transparent,
            borderRadius: BorderRadius.circular(16),
          ),
          child: Row(
            mainAxisAlignment: isExpanded ? MainAxisAlignment.start : MainAxisAlignment.center,
            children: [
              Icon(
                icon,
                size: 20,
                color: isActive
                    ? AppColors.textWhite
                    : AppColors.sidebarInactiveText,
              ),
              if (isExpanded) ...[
                const SizedBox(width: 16),
                Text(
                  label,
                  style: AppTextStyles.bodyMedium.copyWith(
                    color: isActive
                        ? AppColors.textWhite
                        : AppColors.sidebarInactiveText,
                    fontWeight: isActive ? FontWeight.w700 : FontWeight.w500,
                  ),
                ),
              ],
            ],
          ),
        ),
      ),
    );
  }
}

class _SidebarFooter extends StatelessWidget {
  final bool isExpanded;
  const _SidebarFooter({required this.isExpanded});

  @override
  Widget build(BuildContext context) {
    return Container(
      padding: EdgeInsets.symmetric(vertical: 24, horizontal: isExpanded ? 24 : 8),
      child: Row(
        mainAxisAlignment: isExpanded ? MainAxisAlignment.start : MainAxisAlignment.center,
        children: [
          CircleAvatar(
            radius: 20,
            backgroundColor: AppColors.primaryLight,
            child: const Icon(
              Icons.person_rounded,
              color: AppColors.primary,
              size: 20,
            ),
          ),
          if (isExpanded) ...[
            const SizedBox(width: 12),
            Expanded(
              child: Text(
                'Satria', 
                style: AppTextStyles.bodyMedium.copyWith(fontWeight: FontWeight.w700),
                overflow: TextOverflow.ellipsis,
              ),
            ),
          ],
        ],
      ),
    );
  }
}
