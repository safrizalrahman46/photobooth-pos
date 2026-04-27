// features/history/presentation/sections/history_table_section.dart

import 'package:flutter/material.dart';
import '../widgets/history/history_table.dart';
import '../../domain/entities/transaction.dart';

/// Section yang membungkus [HistoryTable].
/// Sekarang tidak lagi menggunakan SingleChildScrollView horizontal 
/// karena tabel sudah menggunakan Flexible/Expanded columns.
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
    // Kita hapus SingleChildScrollView horizontal-nya agar Expanded di dalam tabel 
    // bisa menghitung lebar dengan benar berdasarkan ruang yang tersedia.
    return HistoryTable(
      transactions: transactions,
      onRowAction: onRowAction,
    );
  }
}
