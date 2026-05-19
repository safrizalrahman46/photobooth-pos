import 'package:flutter/material.dart';
import 'package:get/get.dart';
import 'package:intl/intl.dart';
import 'package:desktop_flutter/core/session/api_session.dart';

import '../../../../app/theme/app_colors.dart';
import '../../../../app/theme/app_text_styles.dart';
import '../../application/laporan_controller.dart';
import '../../widgets/summary/summary_card.dart';
import '../../data/laporan_repository_impl.dart';
import '../../../history/domain/entities/transaction.dart';
import '../../widgets/cashflow/cashflow_chart.dart';
import '../../widgets/common/report_table.dart';

class LaporanPage extends StatelessWidget {
  const LaporanPage({super.key});

  @override
  Widget build(BuildContext context) {
    final controller = Get.put(
      LaporanController(repository: LaporanRepositoryImpl()),
    );

    return Scaffold(
      backgroundColor: AppColors.background,
      body: Row(
        mainAxisAlignment: MainAxisAlignment.center,
        children: [
          Expanded(
            child: Obx(() {
              if (controller.isLoading.value) {
                return const Center(child: CircularProgressIndicator());
              }

              if (controller.errorMessage.isNotEmpty) {
                return Center(child: Text(controller.errorMessage.value));
              }

              final summary = controller.summary.value!;

              return SingleChildScrollView(
                padding: const EdgeInsets.fromLTRB(40, 60, 40, 40),
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    /// TITLE
                    Row(
                      mainAxisAlignment: MainAxisAlignment.spaceBetween,
                      children: [
                        Column(
                          crossAxisAlignment: CrossAxisAlignment.start,
                          children: [
                            Text('Laporan Keuangan', style: AppTextStyles.h2),
                            const SizedBox(height: 4),
                            Obx(
                              () => Text(
                                'Data terakhir diperbarui: ${controller.lastUpdated.value}',
                                style: AppTextStyles.bodySmall,
                              ),
                            ),
                          ],
                        ),
                      ],
                    ),

                    const SizedBox(height: 16),

                    /// GRANULAR PICKER (Day/Month/Year selector)
                    Obx(() {
                      final period = controller.selectedPeriod.value;
                      final date = controller.selectedDate.value;
                      String label = "";

                      if (period == LaporanPeriod.hari) {
                        label = DateFormat('dd MMMM yyyy').format(date);
                      } else if (period == LaporanPeriod.minggu) {
                        final weekNum = ((date.day - 1) / 7).floor() + 1;
                        label =
                            "Minggu ke-$weekNum, ${DateFormat('MMMM yyyy').format(date)}";
                      } else if (period == LaporanPeriod.bulan) {
                        label = DateFormat('MMMM yyyy').format(date);
                      } else {
                        label = DateFormat('yyyy').format(date);
                      }

                      return GestureDetector(
                        onTap: () => _selectDate(context, controller),
                        child: Container(
                          padding: const EdgeInsets.symmetric(
                            horizontal: 16,
                            vertical: 10,
                          ),
                          decoration: BoxDecoration(
                            color: Colors.white,
                            borderRadius: BorderRadius.circular(10),
                            border: Border.all(color: const Color(0xFFE2E8F0)),
                          ),
                          child: Row(
                            mainAxisSize: MainAxisSize.min,
                            children: [
                              const Icon(
                                Icons.event_note_rounded,
                                size: 18,
                                color: Color(0xFF64748B),
                              ),
                              const SizedBox(width: 10),
                              Text(
                                label,
                                style: const TextStyle(
                                  fontSize: 14,
                                  fontWeight: FontWeight.w600,
                                  color: Color(0xFF1E293B),
                                ),
                              ),
                              const SizedBox(width: 8),
                              const Icon(
                                Icons.arrow_drop_down_rounded,
                                color: Color(0xFF64748B),
                              ),
                            ],
                          ),
                        ),
                      );
                    }),

                    /// ───────── SUMMARY (2 Cards) ─────────
                    Row(
                      children: [
                        Expanded(
                          child: SummaryCard(
                            icon: Icons.payments_rounded,
                            label: "Total Pendapatan",
                            value: SummaryCard.formatRupiah(
                              summary.totalPendapatan,
                            ),
                            percentageChange: summary.percentageChange,
                          ),
                        ),
                        const SizedBox(width: 20),
                        Expanded(
                          child: SummaryCard(
                            icon: Icons.receipt_long_rounded,
                            label: "Jumlah Booking",
                            value: summary.jumlahBooking.toString(),
                            percentageChange: summary.percentageChange,
                          ),
                        ),
                      ],
                    ),

                    const SizedBox(height: 32),

                    /// ───────── CHART ─────────
                    CashflowChart(totalPendapatan: summary.totalPendapatan),

                    const SizedBox(height: 48),

                    /// ───────── LAPORAN TRANSAKSI ─────────
                    Text(
                      'Rincian Transaksi Terkonfirmasi',
                      style: AppTextStyles.h3,
                    ),
                    const SizedBox(height: 16),
                    FutureBuilder<List<Transaction>>(
                      future: _loadTransactions(),
                      builder: (context, snapshot) {
                        if (snapshot.connectionState ==
                            ConnectionState.waiting) {
                          return const Center(
                            child: CircularProgressIndicator(),
                          );
                        }

                        if (snapshot.hasError) {
                          return Text(
                            snapshot.error.toString(),
                            style: const TextStyle(color: Colors.redAccent),
                          );
                        }

                        return ReportTable(
                          transactions: snapshot.data ?? const <Transaction>[],
                        );
                      },
                    ),

                    const SizedBox(height: 24),
                    // Label info
                    Text(
                      '* Menampilkan transaksi Lunas, Pending, dan Batal dari API.',
                      style: AppTextStyles.caption.copyWith(
                        fontStyle: FontStyle.italic,
                      ),
                    ),
                  ],
                ),
              );
            }),
          ),
        ],
      ),
    );
  }

  Future<void> _selectDate(
    BuildContext context,
    LaporanController controller,
  ) async {
    final DateTime? picked = await showDatePicker(
      context: context,
      initialDate: controller.selectedDate.value,
      firstDate: DateTime(2020),
      lastDate: DateTime.now(),
      builder: (context, child) {
        return Theme(
          data: Theme.of(context).copyWith(
            colorScheme: const ColorScheme.light(
              primary: Color(0xFF6366F1),
              onPrimary: Colors.white,
              onSurface: Color(0xFF1E293B),
            ),
          ),
          child: child!,
        );
      },
    );
    if (picked != null) {
      controller.setDate(picked);
    }
  }

  Future<List<Transaction>> _loadTransactions() async {
    final client = ApiSession.client;

    if (client == null) {
      return <Transaction>[];
    }

    final rows = await client.fetchTransactions(perPage: 50);

    return rows.map((row) {
      final packageItems = row.items
          .where(
            (item) => item.itemType == 'package' || item.itemType == 'booking',
          )
          .toList();
      final addOnItems = row.items
          .where((item) => item.itemType == 'add_on')
          .toList();

      return Transaction(
        id: row.transactionCode,
        waktu: DateTime.tryParse(row.createdAt ?? '') ?? DateTime.now(),
        namaPelanggan: row.customerName.isEmpty ? '-' : row.customerName,
        paket: packageItems.isNotEmpty ? packageItems.first.itemName : '-',
        addOns: addOnItems.isEmpty
            ? null
            : addOnItems.map((item) => item.itemName).join(', '),
        totalBayar: row.totalAmount.round(),
        status: _mapTransactionStatus(row.status),
      );
    }).toList();
  }

  TransactionStatus _mapTransactionStatus(String status) {
    return switch (status) {
      'paid' => TransactionStatus.lunas,
      'void' => TransactionStatus.batal,
      _ => TransactionStatus.pending,
    };
  }
}
