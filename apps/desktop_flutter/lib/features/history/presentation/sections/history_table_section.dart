// features/history/presentation/sections/history_table_section.dart

import 'package:flutter/material.dart';
import '../widgets/history/history_table.dart';
import '../../domain/entities/transaction.dart';

/// Section yang membungkus [HistoryTable] dengan horizontal scroll
/// agar tabel tidak overflow di layar kecil.
///
/// Contoh penggunaan:
/// ```dart
/// HistoryTableSection(
///   transactions: controller.pagedTransactions,
///   onRowAction: controller.onRowAction,
/// )
/// ```
class HistoryTableSection extends StatelessWidget {
  final List<Transaction> transactions;
  final void Function(Transaction) onRowAction;

  const HistoryTableSection({
    super.key,
    required this.transactions,
    required this.onRowAction,
  });

  @override
  Widget build(BuildContext context) {
    return SingleChildScrollView(
      scrollDirection: Axis.horizontal,
      child: ConstrainedBox(
        // Lebar minimum agar tabel tidak terlalu sempit di tablet
        constraints: const BoxConstraints(minWidth: 800),
        child: HistoryTable(
          transactions: transactions,
          onRowAction: onRowAction,
        ),
      ),
    );
  }
}
