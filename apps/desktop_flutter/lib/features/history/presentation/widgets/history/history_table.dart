import 'package:flutter/material.dart';
import '../../../domain/entities/transaction.dart';
import 'history_row.dart';
import 'history_empty.dart';

class HistoryTable extends StatelessWidget {
  final List<Transaction> transactions;
  final void Function(Transaction)? onLunasi;
  final void Function(Transaction)? onReprint;
  final void Function(Transaction)? onExtraPrint;

  static const int _flexId = 2;
  static const int _flexWaktu = 3;
  static const int _flexNama = 3;
  static const int _flexPaket = 3;
  static const int _flexTotal = 2;
  static const int _flexStatus = 3;
  static const double _colAction = 300;

  const HistoryTable({
    super.key,
    required this.transactions,
    this.onLunasi,
    this.onReprint,
    this.onExtraPrint,
  });

  @override
  Widget build(BuildContext context) {
    return Container(
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.circular(24),
        border: Border.all(color: const Color(0xFFE2E8F0)),
        boxShadow: [
          BoxShadow(
            color: Colors.black.withValues(alpha: 0.03),
            blurRadius: 16,
            offset: const Offset(0, 8),
          ),
        ],
      ),
      child: ClipRRect(
        borderRadius: BorderRadius.circular(24),
        child: Column(
          children: [
            _buildHeader(),
            if (transactions.isEmpty)
              const HistoryEmpty()
            else
              ...transactions.map(
                (tx) => HistoryRow(
                  transaction: tx,
                  onLunasi: onLunasi != null ? () => onLunasi!(tx) : null,
                  onReprint: onReprint != null ? () => onReprint!(tx) : null,
                  onExtraPrint: onExtraPrint != null ? () => onExtraPrint!(tx) : null,
                ),
              ),
          ],
        ),
      ),
    );
  }

  Widget _buildHeader() {
    return Container(
      decoration: const BoxDecoration(
        color: Color(0xFFF8FAFC),
        border: Border(bottom: BorderSide(color: Color(0xFFE2E8F0))),
      ),
      padding: const EdgeInsets.symmetric(vertical: 18, horizontal: 24),
      child: Row(
        children: [
          _headerCell('ID TRANSAKSI', _flexId),
          _headerCell('WAKTU', _flexWaktu),
          _headerCell('NAMA PELANGGAN', _flexNama),
          _headerCell('PAKET & ADD-ONS', _flexPaket),
          _headerCell('TOTAL BAYAR', _flexTotal),
          _headerCell('STATUS', _flexStatus),
          const SizedBox(width: _colAction),
        ],
      ),
    );
  }

  Widget _headerCell(String label, int flex) {
    return Expanded(
      flex: flex,
      child: Padding(
        padding: const EdgeInsets.only(right: 16),
        child: Text(
          label,
          style: const TextStyle(
            fontSize: 12,
            fontWeight: FontWeight.w800,
            color: Color(0xFF64748B),
            letterSpacing: 0.75,
          ),
        ),
      ),
    );
  }
}
