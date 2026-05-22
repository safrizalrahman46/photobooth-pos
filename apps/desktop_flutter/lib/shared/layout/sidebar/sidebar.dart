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
    final sidebarWidth = isExpanded ? 224.0 : 72.0;

    return AnimatedContainer(
      duration: const Duration(milliseconds: 250),
      curve: Curves.easeInOut,
      width: sidebarWidth,
      decoration: const BoxDecoration(
        color: AppColors.sidebarBg,
        border: Border(right: BorderSide(color: AppColors.divider, width: 1)),
      ),
      child: ClipRect(
        child: OverflowBox(
          alignment: Alignment.topLeft,
          maxWidth: sidebarWidth,
          minWidth: sidebarWidth,
          child: Column(
            crossAxisAlignment: isExpanded
                ? CrossAxisAlignment.start
                : CrossAxisAlignment.center,
            children: [
              // Logo & Toggle Area
              Padding(
                padding: EdgeInsets.fromLTRB(
                  isExpanded ? 18 : 8,
                  20,
                  isExpanded ? 18 : 8,
                  24,
                ),
                child: Row(
                  mainAxisAlignment: isExpanded
                      ? MainAxisAlignment.spaceBetween
                      : MainAxisAlignment.center,
                  children: [
                    if (isExpanded)
                      Text(
                        'Ready To Pict',
                        style: AppTextStyles.h3.copyWith(
                          fontWeight: FontWeight.w800,
                        ),
                      ),

                    IconButton(
                      onPressed: onToggle,
                      icon: Icon(
                        isExpanded
                            ? Icons.menu_open_rounded
                            : Icons.menu_rounded,
                        color: AppColors.textPrimary,
                        size: 20,
                      ),
                      splashRadius: 18,
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
      end: AppColors.sidebarActive.withValues(alpha: 0.15),
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
                margin: const EdgeInsets.symmetric(horizontal: 12, vertical: 3),
                padding: const EdgeInsets.symmetric(
                  horizontal: 12,
                  vertical: 10,
                ),
                decoration: BoxDecoration(
                  color: bgColor,
                  borderRadius: BorderRadius.circular(14),
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
                  size: 18,
                  color: widget.isActive
                      ? AppColors.textWhite
                      : AppColors.sidebarInactiveText,
                ),
                if (widget.isExpanded) ...[
                  const SizedBox(width: 12),
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
        vertical: 14,
        horizontal: isExpanded ? 12 : 8,
      ),
      child: Column(
        children: [
          Row(
            mainAxisAlignment: isExpanded
                ? MainAxisAlignment.start
                : MainAxisAlignment.center,
            children: [
              CircleAvatar(
                radius: 17,
                backgroundColor: AppColors.primaryLight,
                child: const Icon(
                  Icons.person_rounded,
                  color: AppColors.primary,
                  size: 17,
                ),
              ),
              if (isExpanded) ...[
                const SizedBox(width: 10),
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
          const SizedBox(height: 10),
          Tooltip(
            message: 'Logout',
            child: InkWell(
              onTap: onLogout == null ? null : () => onLogout!(),
              borderRadius: BorderRadius.circular(12),
              child: Container(
                width: double.infinity,
                padding: const EdgeInsets.symmetric(
                  horizontal: 11,
                  vertical: 9,
                ),
                decoration: BoxDecoration(
                  color: const Color(0xFFFFF1F1),
                  borderRadius: BorderRadius.circular(12),
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
                      size: 16,
                    ),
                    if (isExpanded) ...[
                      const SizedBox(width: 8),
                      Text(
                        'Logout',
                        style: AppTextStyles.bodySmall.copyWith(
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
