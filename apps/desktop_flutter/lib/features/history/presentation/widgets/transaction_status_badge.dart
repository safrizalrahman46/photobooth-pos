import 'package:flutter/material.dart';
import '../../domain/entities/transaction.dart';

class TransactionStatusBadge extends StatelessWidget {
  final TransactionStatus status;

  const TransactionStatusBadge({super.key, required this.status});

  @override
  Widget build(BuildContext context) {
    final bgColor = switch (status) {
      TransactionStatus.lunas => const Color(0xFF16A34A),
      TransactionStatus.pending => const Color(0xFFCA8A04),
      TransactionStatus.dp => const Color(0xFFEA580C),
      TransactionStatus.batal => const Color(0xFF6B7280),
    };

    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 4),
      decoration: BoxDecoration(
        color: bgColor.withValues(alpha: 0.12),
        borderRadius: BorderRadius.circular(6),
        border: Border.all(color: bgColor.withValues(alpha: 0.3)),
      ),
      child: Text(
        status.label,
        style: TextStyle(
          color: bgColor,
          fontSize: 11,
          fontWeight: FontWeight.w600,
        ),
      ),
    );
  }
}
