import 'package:flutter/material.dart';
import '../../../app/theme/app_colors.dart';
import '../../../app/theme/app_text_styles.dart';

class SidebarDestination {
  const SidebarDestination({
    required this.id,
    required this.icon,
    required this.label,
  });

  final String id;
  final IconData icon;
  final String label;
}

class Sidebar extends StatelessWidget {
  final String selectedId;
  final ValueChanged<String> onItemTapped;
  final List<SidebarDestination> destinations;
  final bool isExpanded;
  final VoidCallback onToggle;
  final String userName;
  final String userRoleLabel;
  final Future<void> Function()? onLogout;

  const Sidebar({
    super.key,
    required this.selectedId,
    required this.onItemTapped,
    required this.destinations,
    required this.isExpanded,
    required this.onToggle,
    required this.userName,
    required this.userRoleLabel,
    this.onLogout,
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
            crossAxisAlignment: isExpanded
                ? CrossAxisAlignment.start
                : CrossAxisAlignment.center,
            children: [
              // Logo & Toggle Area
              Padding(
                padding: EdgeInsets.fromLTRB(
                  isExpanded ? 20 : 8,
                  24,
                  isExpanded ? 20 : 8,
                  32,
                ),
                child: Row(
                  mainAxisAlignment: isExpanded
                      ? MainAxisAlignment.spaceBetween
                      : MainAxisAlignment.center,
                  children: [
                    if (isExpanded)
                      Text('Ready To Pict', style: AppTextStyles.h2),

                    IconButton(
                      onPressed: onToggle,
                      icon: Icon(
                        isExpanded
                            ? Icons.menu_open_rounded
                            : Icons.menu_rounded,
                        color: AppColors.textPrimary,
                        size: 24,
                      ),
                      splashRadius: 20,
                      tooltip: isExpanded ? 'Tutup Sidebar' : 'Buka Sidebar',
                    ),
                  ],
                ),
              ),

              for (final destination in destinations)
                _SidebarItem(
                  icon: destination.icon,
                  label: destination.label,
                  isActive: selectedId == destination.id,
                  isExpanded: isExpanded,
                  onTap: () => onItemTapped(destination.id),
                ),

              const Spacer(),

              // Footer user
              _SidebarFooter(
                isExpanded: isExpanded,
                userName: userName,
                userRoleLabel: userRoleLabel,
                onLogout: onLogout,
              ),
            ],
          ),
        ),
      ),
    );
  }
}

class _SidebarItem extends StatefulWidget {
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
  State<_SidebarItem> createState() => _SidebarItemState();
}

class _SidebarItemState extends State<_SidebarItem>
    with SingleTickerProviderStateMixin {
  bool _isHovered = false;
  late AnimationController _blinkController;
  late Animation<Color?> _colorAnimation;

  @override
  void initState() {
    super.initState();
    _blinkController = AnimationController(
      vsync: this,
      duration: const Duration(milliseconds: 1200),
    );
    _colorAnimation = ColorTween(
      begin: Colors.transparent,
      end: AppColors.sidebarActive.withOpacity(0.15),
    ).animate(_blinkController);
  }

  @override
  void dispose() {
    _blinkController.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    return MouseRegion(
      onEnter: (_) {
        setState(() => _isHovered = true);
        if (!widget.isActive) _blinkController.repeat(reverse: true);
      },
      onExit: (_) {
        setState(() => _isHovered = false);
        _blinkController.stop();
        _blinkController.reset();
      },
      child: GestureDetector(
        onTap: widget.onTap,
        child: Tooltip(
          message: widget.isExpanded ? '' : widget.label,
          child: AnimatedBuilder(
            animation: _blinkController,
            builder: (context, child) {
              Color bgColor = widget.isActive
                  ? AppColors.sidebarActive
                  : (_isHovered
                        ? (_colorAnimation.value ?? Colors.transparent)
                        : Colors.transparent);

              return Container(
                margin: const EdgeInsets.symmetric(horizontal: 16, vertical: 4),
                padding: const EdgeInsets.symmetric(
                  horizontal: 16,
                  vertical: 12,
                ),
                decoration: BoxDecoration(
                  color: bgColor,
                  borderRadius: BorderRadius.circular(16),
                ),
                child: child,
              );
            },
            child: Row(
              mainAxisAlignment: widget.isExpanded
                  ? MainAxisAlignment.start
                  : MainAxisAlignment.center,
              children: [
                Icon(
                  widget.icon,
                  size: 20,
                  color: widget.isActive
                      ? AppColors.textWhite
                      : AppColors.sidebarInactiveText,
                ),
                if (widget.isExpanded) ...[
                  const SizedBox(width: 16),
                  Text(
                    widget.label,
                    style: AppTextStyles.bodyMedium.copyWith(
                      color: widget.isActive
                          ? AppColors.textWhite
                          : AppColors.sidebarInactiveText,
                      fontWeight: widget.isActive
                          ? FontWeight.w700
                          : FontWeight.w500,
                    ),
                  ),
                ],
              ],
            ),
          ),
        ),
      ),
    );
  }
}

class _SidebarFooter extends StatelessWidget {
  final bool isExpanded;
  final String userName;
  final String userRoleLabel;
  final Future<void> Function()? onLogout;

  const _SidebarFooter({
    required this.isExpanded,
    required this.userName,
    required this.userRoleLabel,
    required this.onLogout,
  });

  @override
  Widget build(BuildContext context) {
    return Padding(
      padding: EdgeInsets.symmetric(
        vertical: 18,
        horizontal: isExpanded ? 16 : 8,
      ),
      child: Column(
        children: [
          Row(
            mainAxisAlignment: isExpanded
                ? MainAxisAlignment.start
                : MainAxisAlignment.center,
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
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Text(
                        userName,
                        style: AppTextStyles.bodyMedium.copyWith(
                          fontWeight: FontWeight.w700,
                        ),
                        overflow: TextOverflow.ellipsis,
                      ),
                      const SizedBox(height: 2),
                      Text(
                        userRoleLabel,
                        style: AppTextStyles.caption.copyWith(
                          color: AppColors.textSecondary,
                        ),
                        overflow: TextOverflow.ellipsis,
                      ),
                    ],
                  ),
                ),
              ],
            ],
          ),
          const SizedBox(height: 12),
          Tooltip(
            message: 'Logout',
            child: InkWell(
              onTap: onLogout == null ? null : () => onLogout!(),
              borderRadius: BorderRadius.circular(14),
              child: Container(
                width: double.infinity,
                padding: const EdgeInsets.symmetric(
                  horizontal: 14,
                  vertical: 11,
                ),
                decoration: BoxDecoration(
                  color: const Color(0xFFFFF1F1),
                  borderRadius: BorderRadius.circular(14),
                  border: Border.all(color: const Color(0xFFFFCDD2)),
                ),
                child: Row(
                  mainAxisAlignment: isExpanded
                      ? MainAxisAlignment.start
                      : MainAxisAlignment.center,
                  children: [
                    const Icon(
                      Icons.logout_rounded,
                      color: Colors.redAccent,
                      size: 18,
                    ),
                    if (isExpanded) ...[
                      const SizedBox(width: 10),
                      Text(
                        'Logout',
                        style: AppTextStyles.bodyMedium.copyWith(
                          color: Colors.redAccent,
                          fontWeight: FontWeight.w700,
                        ),
                      ),
                    ],
                  ],
                ),
              ),
            ),
          ),
        ],
      ),
    );
  }
}
