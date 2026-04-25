import 'package:flutter/material.dart';

class PercentageBadge extends StatelessWidget {
  final double percentage;

  const PercentageBadge({super.key, required this.percentage});

  @override
  Widget build(BuildContext context) {
    final isPositive = percentage >= 0;
    final color = isPositive
        ? const Color(0xFF22C55E)
        : const Color(0xFFEF4444);
    final sign = isPositive ? '+' : '';

    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 4),
      decoration: BoxDecoration(
        color: color.withOpacity(0.1),
        borderRadius: BorderRadius.circular(20),
      ),
      child: Row(
        mainAxisSize: MainAxisSize.min,
        children: [
          Icon(
            isPositive
                ? Icons.trending_up_rounded
                : Icons.trending_down_rounded,
            size: 14,
            color: color,
          ),
          const SizedBox(width: 3),
          Text(
            '$sign${percentage.toStringAsFixed(1)}%',
            style: TextStyle(
              fontSize: 12,
              fontWeight: FontWeight.w600,
              color: color,
            ),
          ),
        ],
      ),
    );
  }
}
