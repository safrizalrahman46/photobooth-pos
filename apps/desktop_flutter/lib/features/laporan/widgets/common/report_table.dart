import 'package:flutter/material.dart';
import 'package:intl/intl.dart';
import '../../../history/domain/entities/transaction.dart';
import '../../../history/presentation/widgets/common/transaction_status_badge.dart';

class ReportTable extends StatelessWidget {
  final List<Transaction> transactions;

  const ReportTable({super.key, required this.transactions});

  static const int _flexId = 2;
  static const int _flexWaktu = 2;
  static const int _flexNama = 3;
  static const int _flexPaket = 3;
  static const int _flexAddon = 3;
  static const int _flexTotal = 2;
  static const int _flexStatus = 2;
  static const double _colAction = 140;

  @override
  Widget build(BuildContext context) {
    return Container(
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.circular(16),
        boxShadow: [
          BoxShadow(
            color: Colors.black.withOpacity(0.04),
            blurRadius: 10,
            offset: const Offset(0, 2),
          ),
        ],
      ),
      child: Column(
        children: [
          _buildHeader(),
          if (transactions.isEmpty)
            const Padding(
              padding: EdgeInsets.all(40),
              child: Text('Tidak ada data transaksi'),
            )
          else
            ...transactions.map((tx) => _ReportRow(transaction: tx)),
        ],
      ),
    );
  }

  Widget _buildHeader() {
    return Container(
      padding: const EdgeInsets.symmetric(vertical: 16, horizontal: 24),
      decoration: const BoxDecoration(
        color: Color(0xFFF9FAFB),
        borderRadius: BorderRadius.only(
          topLeft: Radius.circular(16),
          topRight: Radius.circular(16),
        ),
        border: Border(bottom: BorderSide(color: Color(0xFFE5E7EB))),
      ),
      child: const Row(
        children: [
          _HeaderCell(label: 'ID TRANSAKSI', flex: _flexId),
          _HeaderCell(label: 'WAKTU', flex: _flexWaktu),
          _HeaderCell(label: 'NAMA', flex: _flexNama),
          _HeaderCell(label: 'PAKET', flex: _flexPaket),
          _HeaderCell(label: 'ADD-ON', flex: _flexAddon),
          _HeaderCell(label: 'TOTAL', flex: _flexTotal),
          _HeaderCell(label: 'STATUS', flex: _flexStatus),
          SizedBox(width: _colAction),
        ],
      ),
    );
  }
}

class _HeaderCell extends StatelessWidget {
  final String label;
  final int flex;

  const _HeaderCell({required this.label, required this.flex});

  @override
  Widget build(BuildContext context) {
    return Expanded(
      flex: flex,
      child: Text(
        label,
        style: const TextStyle(
          fontSize: 11,
          fontWeight: FontWeight.w700,
          color: Color(0xFF64748B),
          letterSpacing: 0.5,
        ),
      ),
    );
  }
}

class _ReportRow extends StatelessWidget {
  final Transaction transaction;

  const _ReportRow({required this.transaction});

  @override
  Widget build(BuildContext context) {
    return Container(
      padding: const EdgeInsets.symmetric(vertical: 20, horizontal: 24),
      decoration: const BoxDecoration(
        border: Border(bottom: BorderSide(color: Color(0xFFF1F5F9))),
      ),
      child: Row(
        children: [
          Expanded(
            flex: ReportTable._flexId,
            child: Text(
              transaction.id,
              style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 14),
            ),
          ),
          Expanded(
            flex: ReportTable._flexWaktu,
            child: Text(
              DateFormat('dd Oct, HH:mm').format(transaction.waktu),
              style: const TextStyle(color: Color(0xFF64748B), fontSize: 13),
            ),
          ),
          Expanded(
            flex: ReportTable._flexNama,
            child: Text(
              transaction.namaPelanggan,
              style: const TextStyle(fontWeight: FontWeight.w500, fontSize: 14),
            ),
          ),
          Expanded(
            flex: ReportTable._flexPaket,
            child: Text(
              transaction.paket,
              style: const TextStyle(color: Color(0xFF64748B), fontSize: 13),
            ),
          ),
          Expanded(
            flex: ReportTable._flexAddon,
            child: Text(
              transaction.addOns ?? '-',
              style: const TextStyle(color: Color(0xFF64748B), fontSize: 13),
            ),
          ),
          Expanded(
            flex: ReportTable._flexTotal,
            child: Text(
              NumberFormat.currency(locale: 'id', symbol: 'Rp ', decimalDigits: 0)
                  .format(transaction.totalBayar),
              style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 14),
            ),
          ),
          Expanded(
            flex: ReportTable._flexStatus,
            child: Align(
              alignment: Alignment.centerLeft,
              child: TransactionStatusBadge(status: transaction.status),
            ),
          ),
           SizedBox(
            width: ReportTable._colAction,
            child: Align(
              alignment: Alignment.centerRight,
              child: TextButton.icon(
                onPressed: () {},
                icon: const Icon(Icons.print_rounded, size: 16),
                label: const Text('Cetak'),
                style: TextButton.styleFrom(
                  foregroundColor: const Color(0xFF6366F1),
                  backgroundColor: const Color(0xFFF5F7FF),
                  padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 8),
                  shape: RoundedRectangleBorder(
                    borderRadius: BorderRadius.circular(8),
                    side: const BorderSide(color: Color(0xFFE0E7FF)),
                  ),
                ),
              ),
            ),
          ),
        ],
      ),
    );
  }
}
