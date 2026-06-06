import 'package:flutter/material.dart';
import '../../app/theme/app_colors.dart';

class BaseDialog extends StatelessWidget {
  final Widget child;
  final double? width;
  final double? maxHeight;
  final EdgeInsets padding;

  const BaseDialog({
    super.key,
    required this.child,
    this.width,
    this.maxHeight,
    this.padding = const EdgeInsets.all(24),
  });

  @override
  Widget build(BuildContext context) {
    return Dialog(
      elevation: 0,
      backgroundColor: Colors.transparent,
      child: Container(
        width: width,
        constraints: maxHeight != null
            ? BoxConstraints(maxHeight: maxHeight!)
            : null,
        padding: padding,
        decoration: BoxDecoration(
          color: Colors.white,
          borderRadius: BorderRadius.circular(28),
          border: Border.all(color: AppColors.cardBorder),
          boxShadow: const [
            BoxShadow(
              color: Color(0x1F0F172A),
              blurRadius: 30,
              offset: Offset(0, 18),
            ),
          ],
        ),
        child: child,
      ),
    );
  }
}
