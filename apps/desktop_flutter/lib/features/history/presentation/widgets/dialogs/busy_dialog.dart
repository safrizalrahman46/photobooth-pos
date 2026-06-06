import 'package:flutter/material.dart';
import 'package:desktop_flutter/app/theme/app_colors.dart';

class BusyDialog extends StatelessWidget {
  const BusyDialog({super.key, required this.message});

  final String message;

  @override
  Widget build(BuildContext context) {
    return Center(
      child: Material(
        color: Colors.white,
        borderRadius: BorderRadius.circular(28),
        elevation: 0,
        child: Container(
          padding: const EdgeInsets.symmetric(horizontal: 24, vertical: 20),
          decoration: BoxDecoration(
            color: Colors.white,
            borderRadius: BorderRadius.circular(28),
            border: Border.all(color: AppColors.cardBorder),
          ),
          child: Row(
            mainAxisSize: MainAxisSize.min,
            children: [
              const SizedBox(
                width: 22,
                height: 22,
                child: CircularProgressIndicator(strokeWidth: 2.6),
              ),
              const SizedBox(width: 14),
              Text(message),
            ],
          ),
        ),
      ),
    );
  }
}
