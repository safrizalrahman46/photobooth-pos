import 'package:flutter/material.dart';
import '../widgets/history/history_table.dart';
import '../../domain/entities/transaction.dart';

class HistoryTableSection extends StatelessWidget {
  final List<Transaction> transactions;
  final void Function(Transaction)? onLunasi;
  final void Function(Transaction)? onReprint;
  final void Function(Transaction)? onExtraPrint;

  const HistoryTableSection({
    super.key,
    required this.transactions,
    this.onLunasi,
    this.onReprint,
    this.onExtraPrint,
  });

  @override
  Widget build(BuildContext context) {
    return HistoryTable(
      transactions: transactions,
      onLunasi: onLunasi,
      onReprint: onReprint,
      onExtraPrint: onExtraPrint,
    );
  }
}
